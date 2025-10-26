  </div> <!-- .wrapper -->

  <script>
    // --- Generic UI Logic ---

    // Dropdown Logic
    const userProfile = document.querySelector('.user-profile');
    if (userProfile) {
        userProfile.addEventListener('click', (e) => {
            e.stopPropagation(); // Prevent window click from closing it immediately
            document.querySelector('.dropdown-menu').classList.toggle('show');
        });
    }
    // Close dropdown on click outside
    window.addEventListener('click', (e) => {
        const dropdown = document.querySelector('.dropdown-menu');
        if (dropdown && dropdown.classList.contains('show') && !userProfile.contains(e.target)) {
            dropdown.classList.remove('show');
        }
    });

    // Hamburger Menu for mobile
    const hamburger = document.getElementById('hamburger');
    const sidebar = document.querySelector('.sidebar');
    if (hamburger && sidebar) {
        hamburger.addEventListener('click', () => {
            sidebar.classList.toggle('active-mobile');
        });
    }
  </script>
</body>
</html>