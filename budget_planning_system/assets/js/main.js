// assets/js/main.js

// ── Sidebar toggle (mobile) ───────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  const toggleBtn = document.getElementById('sidebarToggle');
  const sidebar   = document.getElementById('sidebar');
  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', () => sidebar.classList.toggle('show'));
    document.addEventListener('click', e => {
      if (!sidebar.contains(e.target) && e.target !== toggleBtn)
        sidebar.classList.remove('show');
    });
  }

  // Auto-dismiss flash messages
  const flash = document.querySelector('.flash-alert');
  if (flash) setTimeout(() => { flash.style.opacity='0'; flash.style.transition='opacity .5s'; setTimeout(()=>flash.remove(),500); }, 4000);

  // Confirm delete buttons
  document.querySelectorAll('[data-confirm]').forEach(btn => {
    btn.addEventListener('click', e => {
      if (!confirm(btn.dataset.confirm || 'Are you sure?')) e.preventDefault();
    });
  });
});

// ── Render budget doughnut chart ─────────────────────────
function renderBudgetChart(canvasId, labels, spent, limits) {
  const ctx = document.getElementById(canvasId);
  if (!ctx) return;
  const colors = ['#4361ee','#f72585','#7209b7','#06d6a0','#4cc9f0','#ffd166','#3a0ca3','#f3722c'];
  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{ data: spent, backgroundColor: colors, borderWidth: 3, borderColor:'#fff' }]
    },
    options: {
      cutout: '72%', responsive: true, maintainAspectRatio: false,
      plugins: {
        legend: { position:'bottom', labels:{ font:{family:'Nunito',weight:'700'}, padding:12, boxWidth:12 } },
        tooltip: { callbacks: { label: ctx => ' ₹' + Number(ctx.parsed).toLocaleString('en-IN',{minimumFractionDigits:2}) } }
      }
    }
  });
}

// ── Monthly bar chart ────────────────────────────────────
function renderMonthlyChart(canvasId, labels, incomeData, expenseData) {
  const ctx = document.getElementById(canvasId);
  if (!ctx) return;
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [
        { label:'Income',  data:incomeData,  backgroundColor:'rgba(6,214,160,.8)',  borderRadius:6, borderSkipped:false },
        { label:'Expense', data:expenseData, backgroundColor:'rgba(247,37,133,.8)', borderRadius:6, borderSkipped:false }
      ]
    },
    options: {
      responsive:true, maintainAspectRatio:false,
      plugins:{ legend:{ labels:{ font:{family:'Nunito',weight:'700'} } } },
      scales:{
        x:{ grid:{display:false}, ticks:{font:{family:'Nunito',weight:'600'}} },
        y:{ grid:{color:'#f1f5f9'}, ticks:{font:{family:'Nunito',weight:'600'}, callback:v=>'₹'+v.toLocaleString('en-IN')} }
      }
    }
  });
}

// ── Savings line chart ────────────────────────────────────
function renderSavingsChart(canvasId, labels, data) {
  const ctx = document.getElementById(canvasId);
  if (!ctx) return;
  new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets:[{
        label:'Saved Amount', data,
        borderColor:'#4361ee', backgroundColor:'rgba(67,97,238,.1)',
        fill:true, tension:.4, pointBackgroundColor:'#4361ee', pointRadius:5
      }]
    },
    options:{
      responsive:true, maintainAspectRatio:false,
      plugins:{legend:{labels:{font:{family:'Nunito',weight:'700'}}}},
      scales:{
        x:{grid:{display:false}, ticks:{font:{family:'Nunito',weight:'600'}}},
        y:{grid:{color:'#f1f5f9'}, ticks:{font:{family:'Nunito',weight:'600'}, callback:v=>'₹'+v.toLocaleString('en-IN')}}
      }
    }
  });
}
