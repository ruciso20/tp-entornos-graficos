<!-- seccion de Contacto-->

<section class="py-5 bg-light" id="contacto">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-8 col-lg-6">
        <div class="card contact-form shadow-sm">
          <div class="card-body p-4">
            <form id="contactForm">
              <div class="row text-center mb-5">
                <div class="col">
                  <h2 class="fw-bold">📞 Contáctanos</h2>
                  <p class="text-muted fs-5">¿Tienes preguntas? Estamos aquí para ayudarte</p>
                </div>
              </div>
              <div class="row">
                <div class="col-12 col-md-6 mb-3">
                  <label for="contactName" class="form-label">Nombre Completo</label>
                  <input type="text" class="form-control" id="contactName" required>
                </div>
                <div class="col-12 col-md-6 mb-3">
                  <label for="contactEmail" class="form-label">Email</label>
                  <input type="email" class="form-control" id="contactEmail" required>
                </div>
              </div>
              <div class="mb-3">
                <label for="contactSubject" class="form-label">Asunto</label>
                <input type="text" class="form-control" id="contactSubject" required>
              </div>
              <div class="mb-3">
                <label for="contactMessage" class="form-label">Mensaje</label>
                <textarea class="form-control" id="contactMessage" rows="5" required></textarea>
              </div>
              <button type="submit" class="btn btn-primary btn-lg w-100">
                <i class="fas fa-paper-plane me-2"></i> Enviar Mensaje
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>