<?php
// includes/footer.php
?>
  <!-- JS de GLightbox -->
  <script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
  <script>
    // Inicializa todas las
    // <a class="glightbox" ...>
    const lightbox = GLightbox({
      selector: '.glightbox'
    });
  </script>
  <footer style="background: var(--color-light);
                 color: var(--color-muted);
                 text-align: center;
                 padding: 1rem;
                 font-size: 0.9rem;">
    &copy; <?= date('Y') ?> Pinturas Grupo FERRO. Todos los derechos reservados.
  </footer>
  </body>
  </html>
