import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

const COLOR_PALETTE = [
  { count: 'rgba(59, 130, 246, 0.7)', countBorder: 'rgb(59, 130, 246)' },
  { count: 'rgba(234, 88, 12, 0.7)', countBorder: 'rgb(234, 88, 12)' },
  { count: 'rgba(139, 92, 246, 0.7)', countBorder: 'rgb(139, 92, 246)' },
  { count: 'rgba(236, 72, 153, 0.7)', countBorder: 'rgb(236, 72, 153)' },
  { count: 'rgba(20, 184, 166, 0.7)', countBorder: 'rgb(20, 184, 166)' },
];
const AMOUNT_COLOR = { amount: 'rgba(34, 197, 94, 0.6)', amountBorder: 'rgb(34, 197, 94)' };

function initReportChart() {
  const canvas = document.getElementById('reportChart');
  const data = window.businessReportChartData;

  if (!canvas) {
    return;
  }
  if (!data || !Array.isArray(data.monthLabels)) {
    return;
  }

  const ctx = canvas.getContext('2d');
  if (!ctx) {
    return;
  }

  const datasets = [];
  const classrooms = data.classrooms || [];

  classrooms.forEach((cls, i) => {
    const color = COLOR_PALETTE[i % COLOR_PALETTE.length];
    datasets.push({
      label: `${cls.name} 件数`,
      data: cls.counts || [],
      backgroundColor: color.count,
      borderColor: color.countBorder,
      borderWidth: 1,
      yAxisID: 'yCount',
    });
    datasets.push({
      label: `${cls.name} 金額`,
      data: cls.amounts || [],
      backgroundColor: AMOUNT_COLOR.amount,
      borderColor: AMOUNT_COLOR.amountBorder,
      borderWidth: 1,
      yAxisID: 'yAmount',
    });
  });

  const monthLabels = data.monthLabels.length > 0 ? data.monthLabels : ['4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月', '1月', '2月', '3月'];

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: monthLabels,
      datasets,
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: {
        mode: 'index',
        intersect: false,
      },
      plugins: {
        legend: {
          position: 'top',
        },
      },
      scales: {
        x: {
          display: true,
          grid: {
            display: false,
          },
        },
        yCount: {
          type: 'linear',
          position: 'left',
          display: true,
          title: {
            display: true,
            text: '申込件数',
          },
          beginAtZero: true,
          min: 0,
          ticks: {
            stepSize: 1,
          },
        },
        yAmount: {
          type: 'linear',
          position: 'right',
          display: true,
          title: {
            display: true,
            text: '申込金額（円）',
          },
          beginAtZero: true,
          min: 0,
          grid: {
            drawOnChartArea: false,
          },
        },
      },
    },
  });
}

function runWhenReady() {
  if (typeof window.businessReportChartData !== 'undefined' && document.getElementById('reportChart')) {
    initReportChart();
    return;
  }
  const attempts = 50;
  let count = 0;
  const id = setInterval(() => {
    count += 1;
    if (typeof window.businessReportChartData !== 'undefined' && document.getElementById('reportChart')) {
      clearInterval(id);
      initReportChart();
    } else if (count >= attempts) {
      clearInterval(id);
    }
  }, 100);
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', runWhenReady);
} else {
  runWhenReady();
}
