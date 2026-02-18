<template>
  <EditorLayout title="Analytics">

    <div class="page-header">
      <div>
        <h2>Analytics & Insights</h2>
        <p>Track user activity, signups, and match statistics</p>
      </div>
    </div>

    <!-- Stat Cards -->
    <div class="stats-grid mb">
      <div class="stat-card">
        <div class="stat-icon purple">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
        </div>
        <div class="stat-value">{{ stats.totalUsers }}</div>
        <div class="stat-label">Total Users</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon blue">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg>
        </div>
        <div class="stat-value">{{ stats.todaySignups }}</div>
        <div class="stat-label">Signups Today</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon indigo">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>
        </div>
        <div class="stat-value">{{ stats.weekSignups }}</div>
        <div class="stat-label">Signups This Week</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon teal">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
        </div>
        <div class="stat-value">{{ stats.verifiedStudents }}</div>
        <div class="stat-label">Verified Students</div>
      </div>
    </div>

    <div class="stats-grid mb">
      <div class="stat-card">
        <div class="stat-icon pink">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
        </div>
        <div class="stat-value">{{ stats.totalMatches }}</div>
        <div class="stat-label">Total Matches</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon rose">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>
        </div>
        <div class="stat-value">{{ stats.todayMatches }}</div>
        <div class="stat-label">Matches Today</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon orange">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M5 9.2h3V19H5zM10.6 5h2.8v14h-2.8zm5.6 8H19v6h-2.8z"/></svg>
        </div>
        <div class="stat-value">{{ stats.weekMatches }}</div>
        <div class="stat-label">Matches This Week</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon red">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
        </div>
        <div class="stat-value">{{ stats.suspendedUsers }}</div>
        <div class="stat-label">Suspended Users</div>
      </div>
    </div>

    <!-- Charts -->
    <div class="charts-grid mb">
      <div class="card">
        <div class="card-header"><h3>Daily Signups (Last 30 Days)</h3></div>
        <div class="card-body"><canvas ref="signupsChart"></canvas></div>
      </div>
      <div class="card">
        <div class="card-header"><h3>Daily Matches (Last 30 Days)</h3></div>
        <div class="card-body"><canvas ref="matchChart"></canvas></div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3>User Activity Trend (Last 30 Days)</h3></div>
      <div class="card-body"><canvas ref="activityChart"></canvas></div>
    </div>

  </EditorLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import EditorLayout from './Layout/EditorLayout.vue'

const props = defineProps({
  stats:         Object,
  daily_signups: Array,
  match_stats:   Array,
  active_users:  Array,
})

const signupsChart  = ref(null)
const matchChart    = ref(null)
const activityChart = ref(null)

const buildChart = (canvas, data, label, color) => {
  if (!canvas || !data?.length) return
  import('chart.js').then(({ Chart, registerables }) => {
    Chart.register(...registerables)
    new Chart(canvas, {
      type: 'line',
      data: {
        labels: data.map(d => d.date),
        datasets: [{ label, data: data.map(d => d.count), borderColor: color, backgroundColor: color + '20', fill: true, tension: 0.4, pointRadius: 3 }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { x: { ticks: { maxTicksLimit: 10 } }, y: { beginAtZero: true } }
      }
    })
  })
}

onMounted(() => {
  buildChart(signupsChart.value,  props.daily_signups, 'Signups', '#7c3aed')
  buildChart(matchChart.value,    props.match_stats,   'Matches', '#ec4899')
  buildChart(activityChart.value, props.active_users,  'Active',  '#0891b2')
})
</script>

<style scoped>
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
.page-header h2 { font-size: 20px; font-weight: 700; color: #111827; }
.page-header p  { font-size: 14px; color: #6b7280; margin-top: 2px; }
.mb { margin-bottom: 20px; }

.stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
.stat-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; padding: 22px; }
.stat-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 14px; }
.stat-icon svg { width: 22px; height: 22px; }
.stat-icon.purple { background: #ede9fe; color: #7c3aed; }
.stat-icon.blue   { background: #dbeafe; color: #2563eb; }
.stat-icon.indigo { background: #e0e7ff; color: #4338ca; }
.stat-icon.teal   { background: #ccfbf1; color: #0d9488; }
.stat-icon.pink   { background: #fce7f3; color: #be185d; }
.stat-icon.rose   { background: #ffe4e6; color: #e11d48; }
.stat-icon.orange { background: #ffedd5; color: #c2410c; }
.stat-icon.red    { background: #fee2e2; color: #dc2626; }
.stat-value { font-size: 28px; font-weight: 700; color: #111827; line-height: 1; margin-bottom: 4px; }
.stat-label { font-size: 13px; color: #6b7280; font-weight: 500; }

.charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; }
.card-header { padding: 16px 20px; border-bottom: 1px solid #e5e7eb; }
.card-header h3 { font-size: 15px; font-weight: 600; color: #111827; }
.card-body { padding: 20px; }
</style>