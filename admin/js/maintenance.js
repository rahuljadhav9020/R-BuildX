function validateMaintenanceForm() {
    // Add validation logic
    return true;
}

function addPartField() {
    const partsList = document.querySelector('.parts-list');
    const newPart = document.createElement('div');
    newPart.className = 'part-item';
    newPart.innerHTML = `
        <input type="text" name="parts[]" placeholder="Part name">
        <input type="number" name="quantities[]" placeholder="Quantity">
        <input type="number" name="costs[]" placeholder="Cost">
        <button type="button" onclick="this.parentElement.remove()" class="remove-part-btn">-</button>
    `;
    partsList.appendChild(newPart);
}

document.querySelector('select[name="maintenance_type"]').addEventListener('change', function() {
    const sparePartsList = document.getElementById('sparePartsList');
    sparePartsList.style.display = this.value === 'Spare Parts' ? 'block' : 'none';
}); 