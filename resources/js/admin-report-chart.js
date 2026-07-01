import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

const COLORS = {
  issuedUsers: { bg: 'rgba(59, 130, 246, 0.5)', border: 'rgb(59, 130, 246)' },
  usedUsers: { bg: 'rgba(234, 88, 12, 0.5)', border: 'rgb(234, 88, 12)' },
  applicationRate: { bg: 'rgba(139, 92, 246, 0.5)', border: 'rgb(139, 92, 246)' },
  issuedAmount: { bg: 'rgba(34, 197, 94, 0.5)', border: 'rgb(34, 197, 94)' },
  usedAmount: { bg: 'rgba(20, 184, 166, 0.5)', border: 'rgb(20, 184, 166)' },
  balance: { bg: 'rgba(239, 68, 68, 0.5)', border: 'rgb(239, 68, 68)' },
  avgBalance: { bg: 'rgba(236, 72, 153, 0.5)', border: 'rgb(236, 72, 153)' },
  business: { bg: 'rgba(59, 130, 246, 0.5)', border: 'rgb(59, 130, 246)' },
  classroom: { bg: 'rgba(234, 88, 12, 0.5)', border: 'rgb(234, 88, 12)' },
  course: { bg: 'rgba(34, 197, 94, 0.5)', border: 'rgb(34, 197, 94)' },
};

const APPLICATION_APPROVAL_COLORS = {
  userApplication: { bg: 'rgba(59, 130, 246, 0.5)', border: 'rgb(59, 130, 246)' },
  beneficiaryApproval: { bg: 'rgba(34, 197, 94, 0.5)', border: 'rgb(34, 197, 94)' },
  businessApplication: { bg: 'rgba(234, 88, 12, 0.5)', border: 'rgb(234, 88, 12)' },
  businessApproval: { bg: 'rgba(139, 92, 246, 0.5)', border: 'rgb(139, 92, 246)' },
};

function initApplicationApprovalChart() {
  const canvas = document.getElementById('adminReportApplicationApprovalChart');
  const data = window.adminReportChartData;
  const chartData = data?.applicationApprovalChart;

  if (!canvas || !chartData || !Array.isArray(chartData.monthLabels)) {
    return;
  }

  const ctx = canvas.getContext('2d');
  if (!ctx) return;

  const labels = chartData.monthLabels;
  const datasets = [
    {
      label: '利用申請数',
      data: chartData.userApplicationCounts || [],
      borderColor: APPLICATION_APPROVAL_COLORS.userApplication.border,
      backgroundColor: APPLICATION_APPROVAL_COLORS.userApplication.bg,
      fill: false,
      tension: 0.2,
    },
    {
      label: '利用者審査通過数',
      data: chartData.beneficiaryApprovalCounts || [],
      borderColor: APPLICATION_APPROVAL_COLORS.beneficiaryApproval.border,
      backgroundColor: APPLICATION_APPROVAL_COLORS.beneficiaryApproval.bg,
      fill: false,
      tension: 0.2,
    },
    {
      label: '事業者申請数',
      data: chartData.businessApplicationCounts || [],
      borderColor: APPLICATION_APPROVAL_COLORS.businessApplication.border,
      backgroundColor: APPLICATION_APPROVAL_COLORS.businessApplication.bg,
      fill: false,
      tension: 0.2,
    },
    {
      label: '事業者審査通過数',
      data: chartData.businessApprovalCounts || [],
      borderColor: APPLICATION_APPROVAL_COLORS.businessApproval.border,
      backgroundColor: APPLICATION_APPROVAL_COLORS.businessApproval.bg,
      fill: false,
      tension: 0.2,
    },
  ];

  new Chart(ctx, {
    type: 'line',
    data: { labels, datasets },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { position: 'top' },
      },
      scales: {
        x: {
          display: true,
          grid: { display: false },
        },
        y: {
          type: 'linear',
          display: true,
          title: { display: true, text: '件数' },
          beginAtZero: true,
          min: 0,
          ticks: { stepSize: 1 },
        },
      },
    },
  });
}

function initCouponChart() {
  const canvas = document.getElementById('adminReportCouponChart');
  const data = window.adminReportChartData;

  if (!canvas || !data || !Array.isArray(data.monthLabels)) {
    return;
  }

  const ctx = canvas.getContext('2d');
  if (!ctx) return;

  const labels = data.monthLabels;
  const datasets = [
    {
      label: '発行利用者数',
      data: data.issuedUserCounts || [],
      borderColor: COLORS.issuedUsers.border,
      backgroundColor: COLORS.issuedUsers.bg,
      fill: false,
      tension: 0.2,
      yAxisID: 'yLeft',
    },
    {
      label: '利用者数',
      data: data.usedUserCounts || [],
      borderColor: COLORS.usedUsers.border,
      backgroundColor: COLORS.usedUsers.bg,
      fill: false,
      tension: 0.2,
      yAxisID: 'yLeft',
    },
    {
      label: '利用者申請割合（%）',
      data: data.applicationRates || [],
      borderColor: COLORS.applicationRate.border,
      backgroundColor: COLORS.applicationRate.bg,
      fill: false,
      tension: 0.2,
      yAxisID: 'yLeft',
    },
    {
      label: '発行金額（円）',
      data: data.issuedAmounts || [],
      borderColor: COLORS.issuedAmount.border,
      backgroundColor: COLORS.issuedAmount.bg,
      fill: false,
      tension: 0.2,
      yAxisID: 'yRight',
    },
    {
      label: '利用金額（円）',
      data: data.usedAmounts || [],
      borderColor: COLORS.usedAmount.border,
      backgroundColor: COLORS.usedAmount.bg,
      fill: false,
      tension: 0.2,
      yAxisID: 'yRight',
    },
    {
      label: '残高（円）',
      data: data.balances || [],
      borderColor: COLORS.balance.border,
      backgroundColor: COLORS.balance.bg,
      fill: false,
      tension: 0.2,
      yAxisID: 'yRight',
    },
    {
      label: '1人あたり平均残高（円）',
      data: data.avgBalancePerUser || [],
      borderColor: COLORS.avgBalance.border,
      backgroundColor: COLORS.avgBalance.bg,
      fill: false,
      tension: 0.2,
      yAxisID: 'yRight',
    },
  ];

  new Chart(ctx, {
    type: 'line',
    data: { labels, datasets },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { position: 'top' },
      },
      scales: {
        x: {
          display: true,
          grid: { display: false },
        },
        yLeft: {
          type: 'linear',
          position: 'left',
          display: true,
          title: { display: true, text: '人数・割合（%）' },
          beginAtZero: true,
          min: 0,
          grid: { drawOnChartArea: true },
        },
        yRight: {
          type: 'linear',
          position: 'right',
          display: true,
          title: { display: true, text: '金額（円）' },
          beginAtZero: true,
          min: 0,
          grid: { drawOnChartArea: false },
        },
      },
    },
  });
}

function initEntityChart() {
  const canvas = document.getElementById('adminReportEntityChart');
  const data = window.adminReportChartData;

  if (!canvas || !data || !Array.isArray(data.monthLabels)) {
    return;
  }

  const ctx = canvas.getContext('2d');
  if (!ctx) return;

  const labels = data.monthLabels;
  const datasets = [
    {
      label: '事業者数',
      data: data.businessCounts || [],
      borderColor: COLORS.business.border,
      backgroundColor: COLORS.business.bg,
      fill: false,
      tension: 0.2,
    },
    {
      label: '教室数',
      data: data.classroomCounts || [],
      borderColor: COLORS.classroom.border,
      backgroundColor: COLORS.classroom.bg,
      fill: false,
      tension: 0.2,
    },
    {
      label: 'コース数',
      data: data.courseCounts || [],
      borderColor: COLORS.course.border,
      backgroundColor: COLORS.course.bg,
      fill: false,
      tension: 0.2,
    },
  ];

  new Chart(ctx, {
    type: 'line',
    data: { labels, datasets },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { position: 'top' },
      },
      scales: {
        x: {
          display: true,
          grid: { display: false },
        },
        y: {
          type: 'linear',
          display: true,
          title: { display: true, text: '件数' },
          beginAtZero: true,
          min: 0,
          ticks: { stepSize: 1 },
        },
      },
    },
  });
}

const BUMP_CHART_COLORS = [
  'rgb(59, 130, 246)',
  'rgb(234, 88, 12)',
  'rgb(34, 197, 94)',
  'rgb(139, 92, 246)',
  'rgb(236, 72, 153)',
  'rgb(20, 184, 166)',
  'rgb(239, 68, 68)',
  'rgb(251, 146, 60)',
  'rgb(132, 204, 22)',
  'rgb(99, 102, 241)',
  'rgb(244, 63, 94)',
  'rgb(14, 165, 233)',
  'rgb(168, 85, 247)',
  'rgb(34, 197, 94)',
  'rgb(249, 115, 22)',
  'rgb(59, 130, 246)',
  'rgb(190, 24, 93)',
  'rgb(22, 163, 74)',
  'rgb(124, 58, 237)',
  'rgb(234, 88, 12)',
];

function initBumpChart() {
  const canvas = document.getElementById('adminReportBumpChart');
  const data = window.adminReportChartData;
  const bump = data?.bumpChart;

  if (!canvas || !bump || !Array.isArray(bump.monthLabels) || !Array.isArray(bump.classrooms)) {
    return;
  }

  const ctx = canvas.getContext('2d');
  if (!ctx) return;

  const labels = bump.monthLabels;
  const datasets = (bump.classrooms || []).map((cls, i) => ({
    label: cls.name,
    data: cls.ranks || [],
    borderColor: BUMP_CHART_COLORS[i % BUMP_CHART_COLORS.length],
    backgroundColor: BUMP_CHART_COLORS[i % BUMP_CHART_COLORS.length],
    fill: false,
    tension: 0.2,
    spanGaps: false,
    pointRadius: 4,
  }));

  new Chart(ctx, {
    type: 'line',
    data: { labels, datasets },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { position: 'top' },
      },
      scales: {
        x: {
          display: true,
          grid: { display: false },
        },
        y: {
          type: 'linear',
          display: true,
          title: { display: true, text: '順位' },
          min: 1,
          max: 20,
          reverse: true,
          ticks: { stepSize: 1 },
        },
      },
    },
  });
}

const APPLICANT_TYPE_COLORS = {
  corporation: { bg: 'rgba(59, 130, 246, 0.8)', border: 'rgb(59, 130, 246)' },
  voluntary_group: { bg: 'rgba(234, 88, 12, 0.8)', border: 'rgb(234, 88, 12)' },
  individual: { bg: 'rgba(34, 197, 94, 0.8)', border: 'rgb(34, 197, 94)' },
  government_agency: { bg: 'rgba(139, 92, 246, 0.8)', border: 'rgb(139, 92, 246)' },
};

/** 習い事種別グラフ用（最大21系列） */
const LESSON_CATEGORY_PALETTE = [
  'rgba(59, 130, 246, 0.8)',
  'rgba(234, 88, 12, 0.8)',
  'rgba(34, 197, 94, 0.8)',
  'rgba(139, 92, 246, 0.8)',
  'rgba(236, 72, 153, 0.8)',
  'rgba(20, 184, 166, 0.8)',
  'rgba(251, 146, 60, 0.8)',
  'rgba(99, 102, 241, 0.8)',
  'rgba(16, 185, 129, 0.8)',
  'rgba(244, 63, 94, 0.8)',
  'rgba(250, 204, 21, 0.8)',
  'rgba(126, 34, 206, 0.8)',
  'rgba(14, 165, 233, 0.8)',
  'rgba(217, 119, 6, 0.8)',
  'rgba(22, 163, 74, 0.8)',
  'rgba(168, 85, 247, 0.8)',
  'rgba(6, 182, 212, 0.8)',
  'rgba(194, 65, 12, 0.8)',
  'rgba(101, 163, 13, 0.8)',
  'rgba(124, 58, 237, 0.8)',
  'rgba(71, 85, 105, 0.8)',
];

function initUsageByLessonCategoryChart() {
  const canvas = document.getElementById('adminReportUsageByLessonCategoryChart');
  const data = window.adminReportChartData;
  const chartData = data?.usageByLessonCategoryChart;

  if (!canvas || !chartData || !Array.isArray(chartData.monthLabels)) {
    return;
  }

  const labels = chartData.labels || [];
  const series = chartData.series || [];
  if (labels.length === 0 || series.length !== labels.length) {
    return;
  }

  const ctx = canvas.getContext('2d');
  if (!ctx) return;

  const monthLabels = chartData.monthLabels;
  const datasets = labels.map((label, i) => {
    const color = LESSON_CATEGORY_PALETTE[i % LESSON_CATEGORY_PALETTE.length];
    return {
      label,
      data: series[i] || [],
      backgroundColor: color,
      borderColor: color,
      borderWidth: 1,
      stack: 'stack1',
    };
  });

  let maxTotal = 0;
  for (let m = 0; m < monthLabels.length; m++) {
    let t = 0;
    for (let s = 0; s < series.length; s++) {
      t += series[s][m] || 0;
    }
    if (t > maxTotal) maxTotal = t;
  }
  const suggestedMax = maxTotal > 0 ? maxTotal + 1 : 1;

  new Chart(ctx, {
    type: 'bar',
    data: { labels: monthLabels, datasets },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { position: 'top' },
      },
      scales: {
        x: {
          stacked: true,
          display: true,
          title: { display: true, text: '件数' },
          beginAtZero: true,
          suggestedMax,
          ticks: { stepSize: 1 },
        },
        y: {
          stacked: true,
          display: true,
          grid: { display: false },
        },
      },
    },
  });
}

function initApplicantTypeChart() {
  const canvas = document.getElementById('adminReportApplicantTypeChart');
  const data = window.adminReportChartData;
  const chartData = data?.applicantTypeChart;

  if (!canvas || !chartData || !Array.isArray(chartData.monthLabels)) {
    return;
  }

  const ctx = canvas.getContext('2d');
  if (!ctx) return;

  const labels = chartData.monthLabels;
  const corpArr = chartData.corporation || [];
  const volArr = chartData.voluntary_group || [];
  const indArr = chartData.individual || [];
  const govArr = chartData.government_agency || [];
  const datasets = [
    {
      label: '法人',
      data: corpArr,
      backgroundColor: APPLICANT_TYPE_COLORS.corporation.bg,
      borderColor: APPLICANT_TYPE_COLORS.corporation.border,
      borderWidth: 1,
      stack: 'stack1',
    },
    {
      label: '任意団体',
      data: volArr,
      backgroundColor: APPLICANT_TYPE_COLORS.voluntary_group.bg,
      borderColor: APPLICANT_TYPE_COLORS.voluntary_group.border,
      borderWidth: 1,
      stack: 'stack1',
    },
    {
      label: '個人事業主',
      data: indArr,
      backgroundColor: APPLICANT_TYPE_COLORS.individual.bg,
      borderColor: APPLICANT_TYPE_COLORS.individual.border,
      borderWidth: 1,
      stack: 'stack1',
    },
    {
      label: '行政機関',
      data: govArr,
      backgroundColor: APPLICANT_TYPE_COLORS.government_agency.bg,
      borderColor: APPLICANT_TYPE_COLORS.government_agency.border,
      borderWidth: 1,
      stack: 'stack1',
    },
  ];

  let maxTotal = 0;
  for (let i = 0; i < labels.length; i++) {
    const t = (corpArr[i] || 0) + (volArr[i] || 0) + (indArr[i] || 0) + (govArr[i] || 0);
    if (t > maxTotal) maxTotal = t;
  }
  const suggestedMax = maxTotal > 0 ? maxTotal + 1 : 1;

  new Chart(ctx, {
    type: 'bar',
    data: { labels, datasets },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { position: 'top' },
      },
      scales: {
        x: {
          stacked: true,
          display: true,
          title: { display: true, text: '件数' },
          beginAtZero: true,
          suggestedMax,
          ticks: { stepSize: 1 },
        },
        y: {
          stacked: true,
          display: true,
          grid: { display: false },
        },
      },
    },
  });
}

function runWhenReady() {
  if (typeof window.adminReportChartData !== 'undefined') {
    if (document.getElementById('adminReportCouponChart')) {
      initCouponChart();
    }
    if (document.getElementById('adminReportEntityChart')) {
      initEntityChart();
    }
    if (document.getElementById('adminReportBumpChart')) {
      initBumpChart();
    }
    if (document.getElementById('adminReportApplicantTypeChart')) {
      initApplicantTypeChart();
    }
    if (document.getElementById('adminReportUsageByLessonCategoryChart')) {
      initUsageByLessonCategoryChart();
    }
    if (document.getElementById('adminReportApplicationApprovalChart')) {
      initApplicationApprovalChart();
    }
    return;
  }
  let count = 0;
  const id = setInterval(() => {
    count += 1;
    if (typeof window.adminReportChartData !== 'undefined') {
      clearInterval(id);
      if (document.getElementById('adminReportCouponChart')) initCouponChart();
      if (document.getElementById('adminReportEntityChart')) initEntityChart();
      if (document.getElementById('adminReportBumpChart')) initBumpChart();
      if (document.getElementById('adminReportApplicantTypeChart')) initApplicantTypeChart();
      if (document.getElementById('adminReportUsageByLessonCategoryChart')) initUsageByLessonCategoryChart();
      if (document.getElementById('adminReportApplicationApprovalChart')) initApplicationApprovalChart();
    } else if (count >= 50) {
      clearInterval(id);
    }
  }, 100);
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', runWhenReady);
} else {
  runWhenReady();
}
