  </div><!-- .admin-main -->
</div><!-- .admin-shell -->

<script>
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
