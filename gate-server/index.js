const express = require('express');
const http = require('http');
const WebSocket = require('ws');
const mqtt = require('mqtt');
const path = require('path');
const { MongoClient } = require('mongodb'); // Thêm thư viện vừa cài

const PORT = process.env.PORT || 3000;
server.listen(PORT, '0.0.0.0', () => {
    console.log(`Server đang chạy thành công tại cổng: ${PORT}`);
});
const MQTT_BROKER = 'mqtt://broker.hivemq.com:1883';
const BASE_TOPIC = 'gate1';

// Cấu hình MongoDB Atlas (Dùng thông tin từ file db.php của bạn)
const uri = "mongodb+srv://mongodb1234:mongodb1234@cluster0.es7rymc.mongodb.net/?retryWrites=true&w=majority";
const client = new MongoClient(uri);
let db, actionLogCollection;

// Kết nối MongoDB khi khởi động server
async function connectDB() {
  try {
    await client.connect();
    db = client.db('iot_project');
    actionLogCollection = db.collection('action_logs');
    console.log("Connected successfully to MongoDB Atlas");
  } catch (e) {
    console.error("MongoDB Connection Error:", e);
  }
}
connectDB();

const app = express();
// Sửa lỗi nút TẢI (CORS) cho PHP
app.use((req, res, next) => {
  res.header("Access-Control-Allow-Origin", "*");
  next();
});

const server = http.createServer(app);
const wss = new WebSocket.Server({ 
    server,
    path: "/ws" // Thêm dòng này để phân biệt với yêu cầu web thông thường
});

// Hàm lưu sự kiện vào MongoDB thay vì db.json
async function appendEvent(event) {
  // Kiểm tra nếu đã kết nối database thành công mới lưu
  if (actionLogCollection) {
    try {
      await actionLogCollection.insertOne({
        ...event,
        ts: new Date() // Tự động ghi nhận thời gian thực tại server
      });
      console.log("Đã lưu sự kiện vào Atlas:", event.raw);
    } catch (e) {
      console.error("Lỗi khi lưu vào MongoDB:", e);
    }
  } else {
    console.log("Chưa thể lưu vì chưa kết nối được MongoDB Atlas");
  }
}

// API lấy thống kê cho biểu đồ (Đọc từ MongoDB)
app.get('/api/stats/range', async (req, res) => {
  const { start, end } = req.query;
  const days = [];
  let curr = new Date(start);
  const stop = new Date(end);

  while(curr <= stop) {
    const dStr = curr.toISOString().split('T')[0];
    // Đếm số lần OPEN/CLOSE trong ngày từ MongoDB
    const opens = await actionLogCollection.countDocuments({ 
        raw: /OPEN|MO/i, 
        ts: { $gte: new Date(dStr), $lt: new Date(new Date(dStr).getTime() + 86400000) } 
    });
    const closes = await actionLogCollection.countDocuments({ 
        raw: /CLOSE|DONG/i, 
        ts: { $gte: new Date(dStr), $lt: new Date(new Date(dStr).getTime() + 86400000) } 
    });
    days.push({ date: dStr, opens, closes });
    curr.setDate(curr.getDate() + 1);
  }
  res.json({ data: days });
});

// --- GIỮ NGUYÊN LOGIC MQTT & WEBSOCKET CỦA BẠN ---
const mqttClient = mqtt.connect(MQTT_BROKER);
mqttClient.on('connect', () => {
  mqttClient.subscribe([`${BASE_TOPIC}/status`, `${BASE_TOPIC}/event`]);
});

mqttClient.on('message', (topic, message) => {
  const msg = message.toString();
  appendEvent({ topic, raw: msg, source: 'device' });
  // Gửi trạng thái tới các client Web qua WebSocket
  wss.clients.forEach(c => { if(c.readyState === WebSocket.OPEN) c.send(JSON.stringify({type:'status', payload: JSON.parse(msg)})); });
});

wss.on('connection', (ws) => {
  ws.on('message', (data) => {
    const raw = data.toString();
    mqttClient.publish(`${BASE_TOPIC}/command`, raw);
    appendEvent({ topic: `${BASE_TOPIC}/command`, raw, source: 'ui' });
  });
});

server.listen(PORT, () => console.log(`Server running on port ${PORT}`));
