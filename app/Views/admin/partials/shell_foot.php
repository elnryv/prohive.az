  </div><!-- .admin-main -->
</div><!-- .admin-shell -->

<script>
  BirlikdeAdmin.initDrawer(
    document.getElementById('adminBurger'),
    document.getElementById('adminNav'),
    document.getElementById('adminDrawerScrim')
  );
  BirlikdeAdmin.initTableScrollHints();

  var adminCixisBtn = document.getElementById('adminCixisBtn');
  if (adminCixisBtn) {
    adminCixisBtn.addEventListener('click', function (event) {
      event.preventDefault();
      BirlikdeAdmin.api('POST', '/cixis').finally(function () {
        window.location.href = '/giris';
      });
    });
  }
</script>
</body>
</html>
