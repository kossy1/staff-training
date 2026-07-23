// admin/assets/js/charts.js - Chart.js Configuration

$(document).ready(function() {
    'use strict';
    
    // ===== Chart Defaults =====
    Chart.defaults.font.family = "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.color = '#6c757d';
    
    // ===== Color Palette =====
    const colors = {
        primary: '#667eea',
        primaryLight: 'rgba(102, 126, 234, 0.1)',
        secondary: '#764ba2',
        success: '#48bb78',
        danger: '#e74a3b',
        warning: '#f6c23e',
        info: '#36b9cc',
        dark: '#1a1a2e',
        gray: '#6c757d',
        light: '#f8f9fc'
    };
    
    const chartColors = {
        blue: '#4e73df',
        indigo: '#6610f2',
        purple: '#6f42c1',
        pink: '#e83e8c',
        red: '#e74a3b',
        orange: '#fd7e14',
        yellow: '#f6c23e',
        green: '#1cc88a',
        teal: '#20c9a6',
        cyan: '#36b9cc',
        gray: '#858796'
    };
    
    // ===== 1. Dashboard Stats Chart =====
    function initStatsChart() {
        const ctx = document.getElementById('statsChart');
        if (!ctx) return;
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [
                    {
                        label: 'Trainings',
                        data: [12, 19, 15, 22, 18, 24, 28, 32, 38, 42, 45, 50],
                        borderColor: colors.primary,
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: colors.primary,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4
                    },
                    {
                        label: 'Certifications',
                        data: [5, 8, 10, 12, 15, 18, 22, 25, 28, 32, 38, 42],
                        borderColor: colors.success,
                        backgroundColor: 'rgba(72, 187, 120, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: colors.success,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: 'rgba(255,255,255,0.1)',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)',
                            drawBorder: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    }
    
    // ===== 2. Training Distribution Chart =====
    function initTrainingDistributionChart() {
        const ctx = document.getElementById('trainingDistributionChart');
        if (!ctx) return;
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Technical', 'Soft Skills', 'Management', 'Compliance', 'Other'],
                datasets: [{
                    data: [35, 25, 20, 12, 8],
                    backgroundColor: [
                        colors.primary,
                        colors.success,
                        colors.warning,
                        colors.info,
                        colors.secondary
                    ],
                    borderWidth: 3,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: 'rgba(255,255,255,0.1)',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return label + ': ' + percentage + '%';
                            }
                        }
                    }
                },
                animation: {
                    animateRotate: true,
                    animateScale: true
                }
            }
        });
    }
    
    // ===== 3. Employee Training Progress Chart =====
    function initEmployeeProgressChart() {
        const ctx = document.getElementById('employeeProgressChart');
        if (!ctx) return;
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Q1', 'Q2', 'Q3', 'Q4'],
                datasets: [
                    {
                        label: 'Enrolled',
                        data: [65, 78, 90, 85],
                        backgroundColor: 'rgba(102, 126, 234, 0.7)',
                        borderColor: colors.primary,
                        borderWidth: 2,
                        borderRadius: 6
                    },
                    {
                        label: 'Completed',
                        data: [45, 55, 70, 80],
                        backgroundColor: 'rgba(72, 187, 120, 0.7)',
                        borderColor: colors.success,
                        borderWidth: 2,
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: 'rgba(255,255,255,0.1)',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)',
                            drawBorder: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    }
    
    // ===== 4. Department Performance Chart =====
    function initDepartmentChart() {
        const ctx = document.getElementById('departmentChart');
        if (!ctx) return;
        
        new Chart(ctx, {
            type: 'horizontalBar',
            data: {
                labels: ['IT', 'HR', 'Finance', 'Sales', 'Marketing', 'Operations'],
                datasets: [{
                    label: 'Training Completion Rate',
                    data: [92, 85, 78, 88, 82, 90],
                    backgroundColor: [
                        'rgba(102, 126, 234, 0.8)',
                        'rgba(72, 187, 120, 0.8)',
                        'rgba(246, 194, 62, 0.8)',
                        'rgba(54, 185, 204, 0.8)',
                        'rgba(231, 74, 59, 0.8)',
                        'rgba(118, 75, 162, 0.8)'
                    ],
                    borderColor: [
                        colors.primary,
                        colors.success,
                        colors.warning,
                        colors.info,
                        colors.danger,
                        colors.secondary
                    ],
                    borderWidth: 2,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: 'rgba(255,255,255,0.1)',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return 'Completion: ' + context.parsed.x + '%';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 100,
                        grid: {
                            color: 'rgba(0,0,0,0.05)',
                            drawBorder: false
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
    
    // ===== 5. Training Cost vs Value Chart =====
    function initCostValueChart() {
        const ctx = document.getElementById('costValueChart');
        if (!ctx) return;
        
        new Chart(ctx, {
            type: 'scatter',
            data: {
                datasets: [{
                    label: 'Training ROI',
                    data: [
                        { x: 5000, y: 8500 },
                        { x: 8000, y: 12000 },
                        { x: 3000, y: 5500 },
                        { x: 12000, y: 18000 },
                        { x: 6000, y: 9500 },
                        { x: 9000, y: 14000 },
                        { x: 4000, y: 7000 },
                        { x: 15000, y: 22000 }
                    ],
                    backgroundColor: 'rgba(102, 126, 234, 0.6)',
                    borderColor: colors.primary,
                    borderWidth: 2,
                    pointRadius: 8,
                    pointHoverRadius: 12
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: 'rgba(255,255,255,0.1)',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return 'Cost: $' + context.parsed.x.toLocaleString() + 
                                       ' | Value: $' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Training Cost ($)',
                            color: colors.gray
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.05)',
                            drawBorder: false
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Business Value ($)',
                            color: colors.gray
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.05)',
                            drawBorder: false
                        }
                    }
                }
            }
        });
    }
    
    // ===== 6. Certification Status Chart =====
    function initCertificationStatusChart() {
        const ctx = document.getElementById('certificationStatusChart');
        if (!ctx) return;
        
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Active', 'Expired', 'Revoked', 'Pending'],
                datasets: [{
                    data: [65, 20, 5, 10],
                    backgroundColor: [
                        colors.success,
                        colors.warning,
                        colors.danger,
                        colors.info
                    ],
                    borderWidth: 3,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: 'rgba(255,255,255,0.1)',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return label + ': ' + value + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }
    
    // ===== Initialize All Charts =====
    function initCharts() {
        initStatsChart();
        initTrainingDistributionChart();
        initEmployeeProgressChart();
        initDepartmentChart();
        initCostValueChart();
        initCertificationStatusChart();
    }
    
    // ===== Charts Resize Handler =====
    function resizeCharts() {
        Chart.instances.forEach(function(instance) {
            instance.resize();
        });
    }
    
    // ===== Dark Mode Support for Charts =====
    function updateChartColors(isDark) {
        Chart.instances.forEach(function(instance) {
            const isLineChart = instance.config.type === 'line';
            const isBarChart = instance.config.type === 'bar';
            
            if (isLineChart || isBarChart) {
                instance.data.datasets.forEach(function(dataset) {
                    if (isDark) {
                        dataset.borderColor = dataset.borderColor || colors.primary;
                        dataset.backgroundColor = dataset.backgroundColor || 'rgba(102, 126, 234, 0.2)';
                    }
                });
                instance.update();
            }
        });
    }
    
    // ===== Listen for Theme Changes =====
    $(document).on('themeChanged', function(event, isDark) {
        updateChartColors(isDark);
    });
    
    // ===== Initialize on Load =====
    $(document).ready(function() {
        initCharts();
        
        // Resize charts on window resize
        let resizeTimer;
        $(window).on('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                resizeCharts();
            }, 250);
        });
    });
    
    // ===== Export chart functions =====
    window.StaffTrainingCharts = {
        initCharts: initCharts,
        resizeCharts: resizeCharts,
        updateChartColors: updateChartColors
    };
});