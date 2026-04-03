<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tabs - simpler method
    var triggerTabList = document.querySelectorAll('.nav-link-inline[data-bs-toggle="tab"]');
    triggerTabList.forEach(function (triggerEl) {
        triggerEl.addEventListener('click', function (event) {
            event.preventDefault();
            
            // Remove active class from all tabs
            triggerTabList.forEach(function(tab) {
                tab.classList.remove('active');
            });
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Hide all tab panes
            var tabPanes = document.querySelectorAll('.tab-pane');
            tabPanes.forEach(function(pane) {
                pane.classList.remove('show', 'active');
            });
            
            // Show selected tab pane
            var targetId = this.getAttribute('data-bs-target');
            var targetPane = document.querySelector(targetId);
            if (targetPane) {
                targetPane.classList.add('show', 'active');
            }
        });
    });
    
    // Hamburger dropdown menu functionality
    var hamburger = document.querySelector('.hamburger-menu');
    var dropdown = document.getElementById('hamburgerDropdown');

    function toggleDropdown() {
        dropdown.classList.toggle('open');
    }

    if (hamburger) {
        hamburger.addEventListener('click', function(e) {
            e.preventDefault();
            toggleDropdown();
        });
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (dropdown && !dropdown.contains(e.target) && !hamburger.contains(e.target)) {
            dropdown.classList.remove('open');
        }
    });
});
</script>
