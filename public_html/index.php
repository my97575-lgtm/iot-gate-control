<!DOCTYPE html>
<html lang="vi">
  <head>
    <meta charset="utf-8" />
    <title>Điều Khiển Cổng</title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
      /* GIỮ NGUYÊN TOÀN BỘ CSS GỐC CỦA BẠN */
      :root {
        --bg: #f3f7fb; --card: #ffffff; --muted: #475569; --primary: #0ea5e9;
        --danger: #ef4444; --ghost-border: rgba(15, 23, 42, 0.08);
        --border: rgba(15, 23, 42, 0.06); --glass: rgba(2, 6, 23, 0.03);
        --radius: 10px; --shadow: 0 8px 24px rgba(2, 6, 23, 0.06);
      }
      * { box-sizing: border-box }
      html, body, input, button, select, textarea {
        font-family: "Inter", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        -webkit-font-smoothing: antialiased;
      }
      body { margin: 0; background: var(--bg); color: #0f1720; padding: 18px; line-height: 1.35; font-size: 14px; }
      .wrap { max-width: 1100px; margin: 0 auto }
      .header { display: flex; justify-content: space-between; align-items: center }
      h1 { margin: 0; font-size: 18px; font-weight: 700 }
      .small { color: var(--muted); font-size: 13px }
      .tabs { display: flex; gap: 8px; margin-top: 12px }
      .tab { padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border); cursor: pointer; color: var(--muted); background: transparent; font-weight: 600 }
      .tab.active { background: #ecf9ff; color: var(--primary); box-shadow: var(--shadow) }
      .card { background: var(--card); border-radius: var(--radius); padding: 16px; border: 1px solid var(--border); box-shadow: var(--shadow); margin-top: 12px }
      .grid { display: grid; grid-template-columns: 1fr 320px; gap: 18px; align-items: start }
      @media (max-width:980px) { .grid { grid-template-columns: 1fr } }
      .group { padding: 12px; border-radius: 8px; background: var(--glass); border: 1px solid rgba(2, 6, 23, 0.04) }
      .row { display: flex; gap: 12px; align-items: center; flex-wrap: wrap }
      label { max-width: 140px; color: var(--muted); font-weight: 600 }
      input[type=range] { width: 220px }
      input[type=number], input[type="date"] { padding: 8px; border-radius: 6px; border: 1px solid var(--border); background: var(--card); color: inherit; font-family: inherit; }
      .btn { padding: 10px 14px; border-radius: 10px; border: 0; font-weight: 700; cursor: pointer; display: inline-flex; gap: 8px; align-items: center; transition: transform .06s ease, box-shadow .12s ease; font-size: 14px; min-width: 100px; justify-content: center; }
      .btn.primary { background: var(--primary); color: #ffffff; box-shadow: 0 6px 14px rgba(14, 165, 233, 0.08); }
      .btn.danger { background: var(--danger); color: #ffffff; box-shadow: 0 6px 14px rgba(239, 68, 68, 0.06); }
      .btn.ghost { background: transparent; color: var(--muted); border: 1px solid var(--ghost-border); }
      .btn.continue { background: #ffd54d; color: #111; box-shadow: 0 6px 14px rgba(255, 213, 77, 0.08); }
      .btn[disabled] { opacity: .6; cursor: not-allowed; }
      .status { background: var(--card); padding: 14px; border-radius: 10px; border: 1px solid var(--border); box-shadow: var(--shadow) }
      .badge { display: inline-block; padding: 10px 14px; border-radius: 999px; font-weight: 800 }
      .state-open { background: #34d399; color: #022 }
      .state-close { background: #fb924c; color: #2b0e08 }
      .state-stop { background: #e6eef7; color: #0b1220 }
      .stats { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 12px }
      .stat { min-width: 100px; padding: 10px; border-radius: 8px; background: var(--glass); text-align: center; border: 1px solid rgba(2, 6, 23, 0.03) }
      .stat .label { font-size: 12px; color: var(--muted) }
      .stat .val { font-weight: 800; margin-top: 6px }
      .log { height: 220px; overflow: auto; background: #fbfdff; padding: 12px; border-radius: 8px; border: 1px dashed rgba(2, 6, 23, 0.04); font-family: monospace; white-space: pre-wrap; }
      .chart-wrap { margin-top: 12px; padding: 12px; border-radius: 8px; background: var(--glass); border: 1px solid var(--border) }
    </style>
  </head>

  <body>
    <div class="wrap">
      <div class="header">
        <div>
          <h1>Điều Khiển Cổng</h1>
          <div class="small">Điều khiển & Thống kê</div>
        </div>
        <div class="small">WS: <span id="wsStatus">ngắt kết nối</span></div>
      </div>
      <div class="tabs" role="tablist">
        <div class="tab active" data-panel="control">Điều khiển</div>
        <div class="tab" data-panel="stats">Thống kê</div>
      </div>
      <div class="card">
        <div class="grid">
          <div id="panelControl">
            <div class="group">
              <div style="font-weight:800;margin-bottom:8px">Hành động</div>
              <div style="display:flex;gap:10px;flex-wrap:wrap">
                <button id="openBtn" class="btn primary">Mở</button>
                <button id="closeBtn" class="btn danger">Đóng</button>
                <button id="stopBtn" class="btn ghost">Dừng</button>
              </div>
            </div>
            <div class="group" style="margin-top:12px">
              <div style="font-weight:800;margin-bottom:8px">Cấu hình</div>
              <div class="row" style="margin-bottom:8px">
                <label>Tốc độ</label>
                <input id="speed" type="range" min="0" max="255" value="180" />
                <div style="min-width:60px;text-align:center;font-weight:700" id="speedValDisplay">180</div>
                <button id="sendSpeed" class="btn primary">Áp dụng</button>
              </div>
              <div class="row">
                <label>Tự đóng (s)</label>
                <input id="timeout" type="number" min="0" value="0" />
                <button id="sendTimeout" class="btn primary">Áp dụng</button>
                <button id="sendBoth" class="btn primary">Áp dụng cả</button>
              </div>
              <div class="row" style="margin-top:8px">
                <label>Chế độ</label>
                <button id="modeBtn" class="btn ghost">Normal</button>
                <div class="small" id="modeHint">Chế độ hiện tại: Normal</div>
              </div>
            </div>
            <div class="group" style="margin-top:12px">
              <div style="font-weight:800;margin-bottom:8px">Nhật ký</div>
              <div class="log" id="log"></div>
            </div>
          </div>
          <aside id="panelStatus">
            <div class="status">
              <div style="font-weight:800">Trạng thái</div>
              <div style="margin-top:10px"><div id="stateBadge" class="badge state-stop">KHÔNG XÁC ĐỊNH</div></div>
              <div class="stats">
                <div class="stat"><div class="label">Tốc độ</div><div class="val" id="stateSpeed">-</div></div>
                <div class="stat"><div class="label">Tự đóng</div><div class="val" id="stateAuto">-</div></div>
                <div class="stat"><div class="label">Limit1</div><div class="val" id="stateLS1">-</div></div>
                <div class="stat"><div class="label">Limit2</div><div class="val" id="stateLS2">-</div></div>
              </div>
              <div style="margin-top:10px" class="small">Đã gửi lần cuối: <span id="lastSent">-</span></div>
            </div>
          </aside>
        </div>
        <div id="panelStats" style="display:none;margin-top:14px">
          <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:8px">
            <label class="small">Bắt đầu:</label><input id="startDate" type="date" />
            <label class="small">Kết thúc:</label><input id="endDate" type="date" />
            <button id="btnLoad" class="btn primary">Tải</button>
            <button id="btn7" class="btn ghost">7 ngày</button>
            <button id="btn30" class="btn ghost">30 ngày</button>
          </div>
          <div class="chart-wrap"><canvas id="chart" height="300"></canvas></div>
          <div id="summary" style="margin-top:10px" class="small"></div>
        </div>
      </div>
    </div>

    <script>
      // Tự động nhận diện giao thức (ws/wss) và địa chỉ (host/port) từ trình duyệt
      const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
      const wsUrl = `${protocol}//${window.location.host}/ws`;
      let ws = null, lastAction = null, currentMode = 'NORMAL';

      const openBtn = document.getElementById('openBtn');
      const closeBtn = document.getElementById('closeBtn');
      const stopBtn = document.getElementById('stopBtn');
      const logEl = document.getElementById('log');
      const modeBtn = document.getElementById('modeBtn');
      const modeHint = document.getElementById('modeHint');

      function addLog(line) {
        const ts = new Date().toLocaleTimeString('vi-VN');
        logEl.textContent = `[${ts}] ${line}\n\n` + logEl.textContent;
      }

      function connectWs() {
        ws = new WebSocket(wsUrl);
        ws.onopen = () => {
          document.getElementById('wsStatus').innerText = 'đã kết nối';
          document.getElementById('wsStatus').style.color = 'var(--primary)';
          addLog('WS đã kết nối');
        };
        ws.onclose = () => {
          document.getElementById('wsStatus').innerText = 'ngắt kết nối';
          document.getElementById('wsStatus').style.color = '#ef4444';
          setTimeout(connectWs, 2000);
        };
        ws.onmessage = ev => handleIncoming(ev.data);
      }

      function handleIncoming(data) {
        try {
          const obj = JSON.parse(data);
          if (obj.type === 'status') updateStateView(obj.payload);
          else if (obj.type === 'init') {
            if (obj.payload.settings) updateStateView({ speed: obj.payload.settings.speed, timeout: obj.payload.settings.timeout, mode: obj.payload.settings.mode });
            if (obj.payload.lastStatus) updateStateView(obj.payload.lastStatus);
            addLog('Hệ thống đã sẵn sàng');
          }
        } catch (e) { addLog('Dữ liệu thô: ' + data); }
      }

      function setModeUI(m) {
        currentMode = (m || 'NORMAL').toString().toUpperCase();
        if (currentMode === 'AUTO') {
          modeBtn.className = 'btn primary'; modeBtn.innerText = 'Auto'; modeHint.innerText = 'Chế độ hiện tại: Auto';
        } else {
          modeBtn.className = 'btn ghost'; modeBtn.innerText = 'Normal'; modeHint.innerText = 'Chế độ hiện tại: Normal';
        }
      }

      function updateStateView(obj) {
        if (obj.state) {
          const st = obj.state.toUpperCase();
          const badge = document.getElementById('stateBadge');
          badge.innerText = st.includes('OPEN') ? 'ĐANG MỞ' : st.includes('CLOSE') ? 'ĐANG ĐÓNG' : 'DỪNG';
          badge.className = 'badge ' + (st.includes('OPEN') ? 'state-open' : st.includes('CLOSE') ? 'state-close' : 'state-stop');
          
          // Logic nút Dừng / Tiếp tục chuyên nghiệp như cũ
          if (st.includes('OPEN')) { stopBtn.textContent = 'Dừng'; stopBtn.className = 'btn ghost'; lastAction = 'CLOSE'; }
          else if (st.includes('CLOSE')) { stopBtn.textContent = 'Dừng'; stopBtn.className = 'btn ghost'; lastAction = 'OPEN'; }
          else { stopBtn.textContent = 'Tiếp tục'; stopBtn.className = 'btn continue'; }
        }
        if (obj.mode !== undefined) setModeUI(obj.mode);
        if (obj.speed !== undefined) { document.getElementById('stateSpeed').innerText = obj.speed; document.getElementById('speed').value = obj.speed; document.getElementById('speedValDisplay').innerText = obj.speed; }
        if (obj.timeout !== undefined || obj.autoClose !== undefined) { const t = obj.timeout ?? obj.autoClose; document.getElementById('stateAuto').innerText = t; document.getElementById('timeout').value = t; }
        if (obj.ls1 !== undefined) document.getElementById('stateLS1').innerText = obj.ls1 ? 'ON' : 'OFF';
        if (obj.ls2 !== undefined) document.getElementById('stateLS2').innerText = obj.ls2 ? 'ON' : 'OFF';
      }

      function sendCmd(cmd) {
        if (!ws || ws.readyState !== 1) return alert('Chưa kết nối!');
        ws.send(cmd);
        addLog('LỆNH -> ' + cmd);
        document.getElementById('lastSent').innerText = new Date().toLocaleTimeString();
      }

      openBtn.onclick = () => sendCmd('OPEN');
      closeBtn.onclick = () => sendCmd('CLOSE');
      stopBtn.onclick = () => {
        if (stopBtn.textContent === 'Tiếp tục') sendCmd(lastAction || 'OPEN');
        else sendCmd('STOP');
      };
      modeBtn.onclick = () => { const next = currentMode === 'AUTO' ? 'NORMAL' : 'AUTO'; sendCmd('MODE:' + next); setModeUI(next); };
      document.getElementById('sendSpeed').onclick = () => sendCmd('SPEED:' + document.getElementById('speed').value);
      document.getElementById('sendTimeout').onclick = () => sendCmd('TIMEOUT:' + document.getElementById('timeout').value);
      document.getElementById('sendBoth').onclick = () => ws.send(JSON.stringify({ settings: { speed: Number(document.getElementById('speed').value), timeout: Number(document.getElementById('timeout').value) } }));

      // Tab & Thống kê logic
      document.querySelectorAll('.tab').forEach(t => {
        t.onclick = () => {
          document.querySelectorAll('.tab').forEach(x => x.classList.remove('active')); t.classList.add('active');
          const isStats = t.dataset.panel === 'stats';
          document.querySelector('.grid').style.display = isStats ? 'none' : 'grid';
          document.getElementById('panelStats').style.display = isStats ? 'block' : 'none';
        };
      });

      const chartCtx = document.getElementById('chart').getContext('2d');
      let myChart = new Chart(chartCtx, { type: 'bar', data: { labels: [], datasets: [{ label: 'Mở', data: [], backgroundColor: '#06b6d4' }, { label: 'Đóng', data: [], backgroundColor: '#fb923c' }] }, options: { responsive: true } });

      async function loadStats() {
        const s = document.getElementById('startDate').value, e = document.getElementById('endDate').value;
        const res = await fetch(`/api/stats/range?start=${s}&end=${e}`);
        const data = await res.json();
        myChart.data.labels = data.data.map(d => d.date);
        myChart.data.datasets[0].data = data.data.map(d => d.opens);
        myChart.data.datasets[1].data = data.data.map(d => d.closes);
        myChart.update();
        document.getElementById('summary').innerText = `Tổng mở: ${data.data.reduce((a,b)=>a+b.opens,0)} - Tổng đóng: ${data.data.reduce((a,b)=>a+b.closes,0)}`;
      }
      document.getElementById('btnLoad').onclick = loadStats;
      const setRange = (d) => { const end = new Date(), start = new Date(); start.setDate(end.getDate()-(d-1)); document.getElementById('startDate').value = start.toISOString().split('T')[0]; document.getElementById('endDate').value = end.toISOString().split('T')[0]; loadStats(); };
      document.getElementById('btn7').onclick = () => setRange(7);
      document.getElementById('btn30').onclick = () => setRange(30);

      document.getElementById('speed').oninput = (e) => document.getElementById('speedValDisplay').innerText = e.target.value;
      setRange(7); connectWs();
    </script>
  </body>
</html>
