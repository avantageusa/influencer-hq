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
    
    // Hamburger drawer + overlay (IHQ menu)
    var hamburger = document.getElementById('hamburgerMenuBtn') || document.querySelector('.hamburger-menu');
    var dropdown = document.getElementById('hamburgerDropdown');
    var overlay = document.getElementById('hamburgerOverlay');

    function setHmOpen(isOpen) {
        if (!dropdown) return;
        if (overlay) {
            overlay.classList.toggle('open', isOpen);
            overlay.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        }
        dropdown.classList.toggle('open', isOpen);
        if (hamburger) {
            hamburger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            hamburger.setAttribute('aria-label', isOpen ? 'Close menu' : 'Open menu');
        }
        document.body.classList.toggle('hm-drawer-open', isOpen);
    }

    function toggleHm() {
        if (!dropdown) return;
        var next = !dropdown.classList.contains('open');
        setHmOpen(next);
    }

    if (hamburger) {
        hamburger.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            toggleHm();
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function () {
            setHmOpen(false);
        });
    }

    document.addEventListener('click', function (e) {
        if (!dropdown || !dropdown.classList.contains('open')) return;
        if (hamburger && hamburger.contains(e.target)) return;
        if (dropdown.contains(e.target)) return;
        if (overlay && overlay.contains(e.target)) return;
        setHmOpen(false);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && dropdown && dropdown.classList.contains('open')) {
            setHmOpen(false);
        }
    });

    if (dropdown) {
        dropdown.querySelectorAll('a').forEach(function (a) {
            a.addEventListener('click', function () {
                setHmOpen(false);
            });
        });
    }
});
</script>
