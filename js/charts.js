/**
 * Charts.js - Data visualization for Gastos Guard
 * Handles rendering of charts for expenses and budgets
 */

document.addEventListener('DOMContentLoaded', function() {
    // Check if the current page has chart containers
    if (document.getElementById('budget-chart-container') || 
        document.getElementById('expense-chart-container') ||
        document.getElementById('summary-chart-container')) {
        initCharts();
    }
});

/**
 * Initialize charts on the page
 */
function initCharts() {
    // Load data for charts
    Promise.all([
        fetchExpenses(),
        fetchBudgets()
    ]).then(([expenses, budgets]) => {
        if (expenses && budgets) {
            renderBudgetVsExpenseChart(expenses, budgets);
            renderCategoryExpenseChart(expenses);
            renderBudgetProgressCharts(budgets);
            renderMonthlyTrendChart(expenses);
        }
    }).catch(error => {
        console.error('Error initializing charts:', error);
        showErrorNotification('Failed to load chart data');
    });
}

/**
 * Fetch expenses data from API
 * @returns {Promise} Promise resolving to expenses data
 */
function fetchExpenses() {
    return fetch('../api/get_expenses.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({})
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success && data.expenses) {
            return data.expenses;
        } else {
            console.error('No expense data in response:', data);
            return [];
        }
    });
}

/**
 * Fetch budgets data from API
 * @returns {Promise} Promise resolving to budgets data
 */
function fetchBudgets() {
    return fetch('../api/get_budgets.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({})
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success && data.budgets) {
            return data.budgets;
        } else {
            console.error('No budget data in response:', data);
            return [];
        }
    });
}

/**
 * Render chart comparing expenses vs budgets by category
 * @param {Array} expenses - User expenses
 * @param {Array} budgets - User budgets
 */
function renderBudgetVsExpenseChart(expenses, budgets) {
    const container = document.getElementById('budget-chart-container');
    if (!container) return;
    
    // Prepare data structure to compare expenses vs budgets by category
    const categorySummary = {};
    
    // Group budgets by category
    budgets.forEach(budget => {
        if (!categorySummary[budget.category_id]) {
            categorySummary[budget.category_id] = {
                categoryName: budget.category,
                budgetAmount: parseFloat(budget.total) || 0,
                expenseAmount: 0,
                color: budget.color || getRandomColor()
            };
        } else {
            categorySummary[budget.category_id].budgetAmount += parseFloat(budget.total) || 0;
        }
    });
    
    // Group expenses by category
    expenses.forEach(expense => {
        const categoryId = expense.category_id;
        if (categorySummary[categoryId]) {
            categorySummary[categoryId].expenseAmount += parseFloat(expense.amount) || 0;
        } else {
            categorySummary[categoryId] = {
                categoryName: expense.category,
                budgetAmount: 0,
                expenseAmount: parseFloat(expense.amount) || 0,
                color: expense.color || getRandomColor()
            };
        }
    });
    
    // Convert to arrays for chart data
    const categories = [];
    const budgetData = [];
    const expenseData = [];
    const colors = [];
    
    for (const categoryId in categorySummary) {
        if (Object.prototype.hasOwnProperty.call(categorySummary, categoryId)) {
            const item = categorySummary[categoryId];
            categories.push(item.categoryName);
            budgetData.push(item.budgetAmount);
            expenseData.push(item.expenseAmount);
            colors.push(item.color);
        }
    }
    
    // Create chart
    const ctx = document.createElement('canvas');
    ctx.id = 'budget-vs-expense-chart';
    container.appendChild(ctx);
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: categories,
            datasets: [
                {
                    label: 'Budget',
                    data: budgetData,
                    backgroundColor: colors.map(color => `${color}80`), // 50% opacity
                    borderColor: colors,
                    borderWidth: 1
                },
                {
                    label: 'Expenses',
                    data: expenseData,
                    backgroundColor: colors.map(color => `${color}FF`), // Full opacity
                    borderColor: colors,
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Amount (₱)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Category'
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Budget vs Actual Expenses by Category'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.dataset.label || '';
                            const value = context.parsed.y || 0;
                            return `${label}: ₱${value.toFixed(2)}`;
                        }
                    }
                }
            }
        }
    });
}

/**
 * Render expense distribution chart by category
 * @param {Array} expenses - User expenses
 */
function renderCategoryExpenseChart(expenses) {
    const container = document.getElementById('expense-chart-container');
    if (!container) return;
    
    // Group expenses by category
    const categoryTotals = {};
    expenses.forEach(expense => {
        const categoryId = expense.category_id;
        const amount = parseFloat(expense.amount) || 0;
        
        if (!categoryTotals[categoryId]) {
            categoryTotals[categoryId] = {
                category: expense.category,
                total: amount,
                color: expense.color || getRandomColor()
            };
        } else {
            categoryTotals[categoryId].total += amount;
        }
    });
    
    // Convert to arrays for chart
    const labels = [];
    const data = [];
    const colors = [];
    
    for (const categoryId in categoryTotals) {
        if (Object.prototype.hasOwnProperty.call(categoryTotals, categoryId)) {
            labels.push(categoryTotals[categoryId].category);
            data.push(categoryTotals[categoryId].total);
            colors.push(categoryTotals[categoryId].color);
        }
    }
    
    // Create chart
    const ctx = document.createElement('canvas');
    ctx.id = 'category-expense-chart';
    container.appendChild(ctx);
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                },
                title: {
                    display: true,
                    text: 'Expense Distribution by Category'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ₱${value.toFixed(2)} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

/**
 * Render individual budget progress bar charts
 * @param {Array} budgets - User budgets
 */
function renderBudgetProgressCharts(budgets) {
    const container = document.getElementById('budget-progress-container');
    if (!container) return;
    
    // Clear existing content
    container.innerHTML = '';
    
    // Sort budgets by progress percentage (highest first)
    const sortedBudgets = [...budgets].sort((a, b) => 
        parseFloat(b.progress_percentage) - parseFloat(a.progress_percentage)
    );
    
    // Create a chart for each budget
    sortedBudgets.forEach(budget => {
        const budgetId = budget.budget_id;
        const title = budget.title;
        const category = budget.category;
        const total = parseFloat(budget.total) || 0;
        const current = parseFloat(budget.current) || 0;
        const progress = parseFloat(budget.progress_percentage) || 0;
        const color = budget.color || getRandomColor();
        
        // Get status color based on progress
        let statusColor = '#4CAF50'; // Green for normal
        if (progress >= 100) {
            statusColor = '#F44336'; // Red for exceeded
        } else if (progress >= 80) {
            statusColor = '#FF9800'; // Orange for warning
        }
        
        // Create budget progress item
        const budgetItem = document.createElement('div');
        budgetItem.classList.add('budget-progress-item');
        budgetItem.innerHTML = `
            <div class="budget-progress-header">
                <h3>${title}</h3>
                <span class="budget-category">${category}</span>
            </div>
            <div class="budget-progress-stats">
                <span class="budget-amount">₱${total.toFixed(2)}</span>
                <span class="budget-spent">₱${current.toFixed(2)} (${progress.toFixed(0)}%)</span>
            </div>
            <div class="budget-progress-bar-container">
                <canvas id="budget-progress-${budgetId}"></canvas>
            </div>
        `;
        
        container.appendChild(budgetItem);
        
        // Create progress chart
        const ctx = document.getElementById(`budget-progress-${budgetId}`);
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Progress'],
                datasets: [
                    {
                        label: 'Spent',
                        data: [current],
                        backgroundColor: statusColor,
                        borderColor: statusColor,
                        borderWidth: 1
                    },
                    {
                        label: 'Remaining',
                        data: [Math.max(0, total - current)],
                        backgroundColor: '#E0E0E0',
                        borderColor: '#BDBDBD',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        stacked: true,
                        display: false,
                        max: total * 1.1 // Give 10% extra space
                    },
                    y: {
                        stacked: true,
                        display: false
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.dataset.label || '';
                                const value = context.parsed.x || 0;
                                return `${label}: ₱${value.toFixed(2)}`;
                            }
                        }
                    }
                }
            }
        });
    });
}

/**
 * Render monthly expense trend chart
 * @param {Array} expenses - User expenses
 */
function renderMonthlyTrendChart(expenses) {
    const container = document.getElementById('summary-chart-container');
    if (!container) return;
    
    // Group expenses by month
    const monthlyData = {};
    
    expenses.forEach(expense => {
        const date = new Date(expense.date_spent);
        const month = date.getMonth();
        const year = date.getFullYear();
        const monthKey = `${year}-${month + 1}`;
        const amount = parseFloat(expense.amount) || 0;
        
        if (!monthlyData[monthKey]) {
            monthlyData[monthKey] = {
                label: new Date(year, month, 1).toLocaleDateString('en-US', { month: 'short', year: 'numeric' }),
                total: amount,
                timestamp: new Date(year, month, 1).getTime()
            };
        } else {
            monthlyData[monthKey].total += amount;
        }
    });
    
    // Sort data by date and prepare chart data
    const sortedMonths = Object.values(monthlyData)
        .sort((a, b) => a.timestamp - b.timestamp);
    
    const labels = sortedMonths.map(item => item.label);
    const data = sortedMonths.map(item => item.total);
    
    // Create chart
    const ctx = document.createElement('canvas');
    ctx.id = 'monthly-trend-chart';
    container.appendChild(ctx);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Monthly Expenses',
                data: data,
                fill: false,
                borderColor: '#3F51B5',
                tension: 0.1,
                pointBackgroundColor: '#3F51B5',
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Amount (₱)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Month'
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Monthly Expense Trend'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.dataset.label || '';
                            const value = context.parsed.y || 0;
                            return `${label}: ₱${value.toFixed(2)}`;
                        }
                    }
                }
            }
        }
    });
}

/**
 * Generate random color for chart elements
 * @returns {string} Random hex color
 */
function getRandomColor() {
    const letters = '0123456789ABCDEF';
    let color = '#';
    for (let i = 0; i < 6; i++) {
        color += letters[Math.floor(Math.random() * 16)];
    }
    return color;
}

/**
 * Display error notification
 * @param {string} message - Error message to display
 */
function showErrorNotification(message) {
    const systemMessage = document.getElementById('system-message');
    if (systemMessage) {
        systemMessage.textContent = message;
        systemMessage.classList.add('error');
        systemMessage.style.display = 'block';
        
        setTimeout(() => {
            systemMessage.style.display = 'none';
            systemMessage.classList.remove('error');
        }, 5000);
    }
}

/**
 * Fetch and display budget usage by date range
 * @param {string} startDate - Start date for filter
 * @param {string} endDate - End date for filter
 */
function fetchBudgetUsageByDateRange(startDate, endDate) {
    // Only proceed if both dates are provided
    if (!startDate || !endDate) return;
    
    const filters = {
        start_date: startDate,
        end_date: endDate
    };
    
    Promise.all([
        fetch('../api/get_expenses.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(filters)
        }).then(res => res.json()),
        
        fetch('../api/get_budgets.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(filters)
        }).then(res => res.json())
    ])
    .then(([expensesData, budgetsData]) => {
        if (expensesData.success && budgetsData.success) {
            // Re-render charts with filtered data
            if (document.getElementById('budget-chart-container')) {
                document.getElementById('budget-chart-container').innerHTML = '';
                renderBudgetVsExpenseChart(expensesData.expenses, budgetsData.budgets);
            }
            
            if (document.getElementById('expense-chart-container')) {
                document.getElementById('expense-chart-container').innerHTML = '';
                renderCategoryExpenseChart(expensesData.expenses);
            }
            
            if (document.getElementById('budget-progress-container')) {
                renderBudgetProgressCharts(budgetsData.budgets);
            }
        }
    })
    .catch(error => {
        console.error('Error fetching filtered data:', error);
        showErrorNotification('Failed to load filtered data');
    });
}

/**
 * Export charts as images
 * @param {string} chartId - ID of the chart to export
 * @param {string} filename - Name for the exported file
 */
function exportChartAsImage(chartId, filename) {
    const canvas = document.getElementById(chartId);
    if (!canvas) return;
    
    // Convert canvas to image
    const image = canvas.toDataURL('image/png', 1.0);
    
    // Create download link
    const downloadLink = document.createElement('a');
    downloadLink.href = image;
    downloadLink.download = `${filename}.png`;
    
    // Append to document, click, and remove
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

/**
 * Initialize date range picker for chart filters
 */
function initDateRangePicker() {
    const startDateInput = document.getElementById('chart-start-date');
    const endDateInput = document.getElementById('chart-end-date');
    const applyButton = document.getElementById('apply-chart-filter');
    
    if (startDateInput && endDateInput && applyButton) {
        applyButton.addEventListener('click', function() {
            const startDate = startDateInput.value;
            const endDate = endDateInput.value;
            
            if (startDate && endDate) {
                if (new Date(endDate) < new Date(startDate)) {
                    showErrorNotification('End date must be after start date');
                    return;
                }
                
                fetchBudgetUsageByDateRange(startDate, endDate);
            } else {
                showErrorNotification('Please select both start and end dates');
            }
        });
    }
}

// Initialize date range picker if available
if (document.getElementById('chart-start-date')) {
    initDateRangePicker();
}

// Export functions for external use
window.GastosCharts = {
    initCharts,
    exportChartAsImage,
    fetchBudgetUsageByDateRange
};