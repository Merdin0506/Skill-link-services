const ROLE_TEMPLATES = {
  super_admin: {
    title: 'Admin Actions',
    description: 'Operational overview for system administrators.',
    chartLeftTitle: 'Bookings by Status',
    chartLeftFallback: 'No booking status data yet.',
    chartRightTitle: 'Bookings by Priority',
    chartRightFallback: 'No booking priority data yet.',
    tableTitle: 'Recent Bookings',
    tableHeaders: ['Reference', 'Title', 'Status', 'Priority', 'Amount', 'Date'],
    tableMode: 'admin',
    actions: ['Manage Users', 'View Reports', 'System Settings']
  },
  admin: {
    title: 'Admin Actions',
    description: 'Operational overview for system administrators.',
    chartLeftTitle: 'Bookings by Status',
    chartLeftFallback: 'No booking status data yet.',
    chartRightTitle: 'Bookings by Priority',
    chartRightFallback: 'No booking priority data yet.',
    tableTitle: 'Recent Bookings',
    tableHeaders: ['Reference', 'Title', 'Status', 'Priority', 'Amount', 'Date'],
    tableMode: 'admin',
    actions: ['Manage Users', 'View Reports', 'System Settings']
  },
  finance: {
    title: 'Finance Dashboard',
    description: 'Payment flow summary and payout controls for the finance team.',
    chartLeftTitle: 'Revenue Trend (Last 7 Days)',
    chartLeftFallback: 'No revenue trend data yet.',
    chartRightTitle: 'Payment Methods',
    chartRightFallback: 'No payment method data yet.',
    tableTitle: 'Recent Transactions',
    tableHeaders: ['Transaction ID', 'Booking Reference', 'Amount', 'Payment Method', 'Status', 'Date', 'Actions'],
    tableMode: 'finance',
    actions: ['View Payments', 'Generate Invoices', 'Financial Reports']
  },
  worker: {
    title: 'Worker Dashboard',
    description: 'Current workload, earnings, and completion performance for workers.',
    chartLeftTitle: 'Earnings Trend (Last 30 Days)',
    chartLeftFallback: 'No earnings data yet.',
    chartRightTitle: 'Job Completion Rate',
    chartRightFallback: 'No completion data yet.',
    tableTitle: 'Recent Jobs',
    tableHeaders: ['Reference', 'Title', 'Status', 'Scheduled Date', 'Earnings', 'Actions'],
    tableMode: 'worker',
    actions: ['View Job Offers', 'My Skills', 'Earnings & Payments']
  },
  customer: {
    title: 'Customer Dashboard',
    description: 'Bookings, spending, and service activity for customers.',
    chartLeftTitle: 'Spending Trend (Last 30 Days)',
    chartLeftFallback: 'No spending data yet.',
    chartRightTitle: 'Service Preferences',
    chartRightFallback: 'No service preference data yet.',
    tableTitle: 'Recent Bookings',
    tableHeaders: ['Reference', 'Title', 'Status', 'Scheduled Date', 'Amount', 'Actions'],
    tableMode: 'customer',
    actions: ['Find Services', 'My Projects', 'Messages']
  }
};

export function getRoleTemplate(role) {
  return ROLE_TEMPLATES[role] || {
    title: 'Dashboard',
    description: 'Role-based dashboard overview.',
    chartLeftTitle: 'Overview',
    chartLeftFallback: 'No data available.',
    chartRightTitle: 'Activity',
    chartRightFallback: 'No data available.',
    tableTitle: 'Recent Activity',
    tableHeaders: ['Reference', 'Title', 'Status', 'Date', 'Actions'],
    tableMode: 'generic',
    actions: ['Overview', 'Recent Activity', 'Account Settings']
  };
}
