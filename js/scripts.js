document.getElementById('is_pob').addEventListener('change', function() {
    var pobContainer = document.getElementById('pob_version_container');
    if (this.value === 'Yes') {
        pobContainer.style.display = 'block';
    } else {
        pobContainer.style.display = 'none';
    }
});
