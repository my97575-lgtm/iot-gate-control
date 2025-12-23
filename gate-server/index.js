const express = require('express');
const http = require('http');
const WebSocket = require('ws');
const mqtt = require('mqtt');
const path = require('path');
const { MongoClient } = require('mongodb');

const app = express();
const server = http.createServer(app); // Tạo server trước khi gọi listen

// 1. Cấu hình WebSocket với path rõ ràng
const wss = new WebSocket.Server({ 
    server, 
    path: "/ws" 
});

const PORT = process.env.PORT || 3000;
const MQTT_BROKER = 'mqtt://broker.hivemq.com:1883';
const BASE_TOPIC = 'gate1';

// 2. Cấu hình MongoDB Atlas
const uri = "mongodb+srv://mongodb1234:mongodb1234@cluster0.es7rymc.mongodb.net/?retryWrites=true&w=majority";
const client = new MongoClient(uri);
let db, actionLogCollection;

async function connectDB() {
    try {
        await client.connect();
        db = client.db('iot_project');
        actionLogCollection = db.collection('action_logs');
        console.log("✅ Connected successfully to MongoDB Atlas");
    } catch (e) {
        console.error("❌ MongoDB Connection Error:", e);
    }
}
connectDB();

// 3. Middlewares & Static Files
app.use((req, res, next) => {
    res.header("Access-Control-Allow-Origin", "*");
    next();
});
// Nếu bạn để file HTML trong thư mục 'public', hãy dùng dòng này:
app.use(express.static(path.join(__dirname, 'public'))); 
// Hoặc nếu file HTML nằm cùng thư mục index.js:
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'index.html'));
});

// 4. API lấy thống kê
app.get('/api/stats/range', async (req, res) => {
    try {
        const { start, end } = req.query;
        const days = [];
        let curr = new Date(start);
        const stop = new Date(end);

        while(curr <= stop) {
            const dStr = curr.toISOString().split('T')[0];
            const nextDay = new Date(curr.getTime() + 86400000);
            
            const opens = await actionLogCollection.countDocuments({ 
                raw: /OPEN|MO/i, 
                ts: { $gte: new Date(dStr), $lt: nextDay } 
            });
            const closes = await actionLogCollection.countDocuments({ 
                raw: /CLOSE|DONG/i, 
                ts: { $gte: new Date(dStr), $lt: nextDay } 
            });
            
            days.push({ date: dStr, opens, closes });
            curr.setDate(curr.getDate() + 1);
        }
        res.json({ data: days });
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
});

// 5. Hàm lưu sự kiện
async function appendEvent(event) {
    if (actionLogCollection) {
        try {
            await actionLogCollection.insertOne({
                ...event,
                ts: new Date()
            });
        } catch (e) {
            console.error("Lỗi lưu DB:", e);
        }
    }
}

// 6. MQTT Logic
const mqttClient = mqtt.connect(MQTT_BROKER);
mqttClient.on('connect', () => {
    console.log("✅ Connected to MQTT Broker");
    mqttClient.subscribe([`${BASE_TOPIC}/status`, `${BASE_TOPIC}/event`]);
});

mqttClient.on('message', (topic, message) => {
    const msg = message.toString();
    appendEvent({ topic, raw: msg, source: 'device' });
    
    // Gửi cho các client đang kết nối qua WS
    wss.clients.forEach(c => {
        if(c.readyState === WebSocket.OPEN) {
            try {
                c.send(JSON.stringify({type:'status', payload: JSON.parse(msg)}));
            } catch(e) {
                c.send(JSON.stringify({type:'status', payload: msg}));
            }
        }
    });
});

// 7. WebSocket Logic
wss.on('connection', (ws) => {
    console.log("🔌 New Web Client connected");
    ws.on('message', (data) => {
        const raw = data.toString();
        mqttClient.publish(`${BASE_TOPIC}/command`, raw);
        appendEvent({ topic: `${BASE_TOPIC}/command`, raw, source: 'ui' });
    });
});

// 8. Khởi động Server (CHỈ GỌI 1 LẦN Ở CUỐI)
server.listen(PORT, '0.0.0.0', () => {
    console.log(`🚀 Server is running on port ${PORT}`);
});
