  </div> <!-- .wrapper -->

  <script>
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