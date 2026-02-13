function generateReport() {
    const type = document.getElementById('reportType').value;
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    // Add your report generation logic here
    console.log('Generating report:', {type, startDate, endDate});
}

function exportReport() {
    // Add your export logic here
    console.log('Exporting report');
}

function validateMaintenanceForm() {
    const form = document.querySelector('.maintenance-form');
    const totalCost = form.querySelector('input[name="total_cost"]').value;
    
    if (!totalCost || totalCost <= 0) {
        alert('Please enter a valid total cost');
        return false;
    }
    
    return true;
} 