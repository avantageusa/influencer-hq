<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dynamically offset dealer-image-container below fixed headers (mobile only)
    function adjustDealerMargin() {
        var dealerContainer = document.querySelector('.dealer-image-container');
        if (!dealerContainer) return;
        if (window.innerWidth >= 1024) {
            dealerContainer.style.marginTop = '';
            return;
        }
        var searchBar = document.querySelector('.search-bar-container');
        var stickyHeader = document.querySelector('.sticky-header');
        var stickyNav = document.querySelector('.sticky-nav');
        var total = 0;
        if (searchBar)    total += searchBar.offsetHeight;
        if (stickyHeader) total += stickyHeader.offsetHeight;
        if (stickyNav)    total += stickyNav.offsetHeight;
        dealerContainer.style.marginTop = total + 'px';
    }
    adjustDealerMargin();
    window.addEventListener('resize', adjustDealerMargin);

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
