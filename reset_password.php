<?php
session_start();
include("config/db.php");

$error = "";
$success = "";

// Verificar token
if (isset($_GET['token'])) {
  $token = $_GET['token'];

  // Buscar usuario con token válido y no expirado
  $stmt = $conn->prepare("SELECT id, token_expiracion FROM usuarios WHERE token_recuperacion = ?");
  $stmt->bind_param("s", $token);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows == 1) {
    $user = $result->fetch_assoc();

    // Verificar si el token no ha expirado
    if (strtotime($user['token_expiracion']) > time()) {
      $token_valido = true;
      $user_id = $user['id'];
    } else {
      $error = "El enlace de recuperación ha expirado. Por favor, solicita uno nuevo.";
    }
  } else {
    $error = "Enlace de recuperación inválido.";
  }
} else {
  $error = "No se proporcionó un token válido.";
}

// Procesar cambio de contraseña
if (isset($_POST['cambiar_password']) && isset($token_valido)) {
  $password = $_POST['password'];
  $password_repeat = $_POST['password_repeat'];

  if (empty($password) || empty($password_repeat)) {
    $error = "Ambos campos son obligatorios";
  } elseif (strlen($password) < 8) {
    $error = "La contraseña debe tener al menos 8 caracteres";
  } elseif (!preg_match('/[A-Z]/', $password)) {
    $error = "La contraseña debe contener al menos una letra mayúscula";
  } elseif (!preg_match('/[0-9]/', $password)) {
    $error = "La contraseña debe contener al menos un número";
  } elseif (!preg_match('/[!@#$%^&*()\-_=+{};:,<.>]/', $password)) {
    $error = "La contraseña debe contener al menos un carácter especial (!@#$%^&*)";
  } elseif ($password !== $password_repeat) {
    $error = "Las contraseñas no coinciden";
  } else {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Actualizar contraseña y limpiamos token
    $stmt = $conn->prepare("UPDATE usuarios SET password = ?, token_recuperacion = NULL, token_expiracion = NULL WHERE id = ?");
    $stmt->bind_param("si", $hashed_password, $user_id);

    if ($stmt->execute()) {
      $success = "✅ Contraseña actualizada correctamente. Ahora puedes iniciar sesión con tu nueva contraseña.";
      $cambio_exitoso = true;
    } else {
      $error = "Error al actualizar la contraseña.";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Restablecer Contraseña - Stella Shopping Rosario</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    .password-container {
      position: relative;
    }

    .password-container input {
      padding-right: 40px;
    }

    .toggle-password {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: #6c757d;
      cursor: pointer;
      z-index: 10;
      padding: 5px;
    }

    .toggle-password:hover {
      color: #495057;
    }

    /* Ocultar controles nativos */
    input[type="password"]::-webkit-credentials-auto-fill-button {
      display: none !important;
    }

    /* Barra de fortaleza de contraseña */
    .password-strength {
      height: 5px;
      margin-top: 5px;
      border-radius: 3px;
      transition: all 0.3s ease;
    }

    .strength-weak {
      width: 25%;
      background-color: #dc3545;
    }

    .strength-medium {
      width: 50%;
      background-color: #ffc107;
    }

    .strength-good {
      width: 75%;
      background-color: #17a2b8;
    }

    .strength-strong {
      width: 100%;
      background-color: #28a745;
    }

    /* Lista de requisitos */
    .requirement-list {
      list-style: none;
      padding-left: 0;
      margin-top: 5px;
      font-size: 0.85rem;
    }

    .requirement-list li {
      margin-bottom: 3px;
    }

    .requirement-valid {
      color: #28a745;
    }

    .requirement-invalid {
      color: #6c757d;
    }

    .requirement-valid i {
      margin-right: 5px;
    }

    .requirement-invalid i {
      margin-right: 5px;
    }
  </style>
</head>

<body class="bg-light">
  <div class="container mt-5">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card shadow">
          <div class="card-header bg-warning text-dark">
            <h4 class="mb-0">Restablecer Contraseña</h4>
          </div>
          <div class="card-body">
            <?php if ($error): ?>
              <div class="alert alert-danger">
                <?php echo $error; ?>
                <div class="mt-3">
                  <a href="login.php?recuperar=1" class="btn btn-outline-warning">Solicitar nuevo enlace</a>
                  <a href="login.php" class="btn btn-outline-primary">Volver al Login</a>
                </div>
              </div>
            <?php elseif ($success): ?>
              <div class="alert alert-success">
                <?php echo $success; ?>
                <div class="mt-3">
                  <a href="login.php" class="btn btn-primary">🔐 Iniciar Sesión</a>
                </div>
              </div>
            <?php elseif (isset($token_valido)): ?>
              <p class="mb-4">Ingresa tu nueva contraseña:</p>
              <form method="POST" id="resetForm">
                <div class="mb-3">
                  <label class="form-label">Nueva Contraseña *</label>
                  <div class="password-container">
                    <input type="password" class="form-control" name="password" id="password" required
                      placeholder="Mínimo 8 caracteres con mayúscula, número y símbolo">
                    <button type="button" class="toggle-password" onclick="togglePassword('password')">
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                  <div class="password-strength mt-2" id="passwordStrengthBar"></div>
                  <small class="text-muted" id="passwordStrengthText">Fortaleza: </small>

                  <ul class="requirement-list mt-2" id="passwordRequirements">
                    <li id="reqLength"><i class="fas fa-circle"></i> Al menos 8 caracteres</li>
                    <li id="reqUppercase"><i class="fas fa-circle"></i> Al menos una mayúscula</li>
                    <li id="reqNumber"><i class="fas fa-circle"></i> Al menos un número</li>
                    <li id="reqSpecial"><i class="fas fa-circle"></i> Al menos un símbolo (!@#$%^&*)</li>
                  </ul>
                </div>

                <div class="mb-4">
                  <label class="form-label">Repetir Contraseña *</label>
                  <div class="password-container">
                    <input type="password" class="form-control" name="password_repeat" id="password_repeat" required minlength="6"
                      placeholder="Repite tu contraseña">
                    <button type="button" class="toggle-password" onclick="togglePassword('password_repeat')">
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                  <div class="form-text" id="passwordMatchText">Las contraseñas deben coincidir</div>
                </div>

                <button type="submit" name="cambiar_password" class="btn btn-warning w-100 mb-3">
                  <i class="fas fa-save me-2"></i>Cambiar Contraseña
                </button>
              </form>

              <div class="text-center mt-3">
                <a href="login.php" class="btn btn-outline-secondary btn-sm">🔐 Volver al Login</a>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    function togglePassword(fieldId) {
      const field = document.getElementById(fieldId);
      const button = field.parentNode.querySelector('.toggle-password');
      const icon = button.querySelector('i');

      if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      }
    }

    // funcion para evaluar la fortaleza 
    function checkPasswordStrength(password) {
      let score = 0;
      let strength = 'débil';
      let color = '#dc3545';

      // Verificamos los requisitos minimos
      const hasLength = password.length >= 8;
      const hasUppercase = /[A-Z]/.test(password);
      const hasNumber = /[0-9]/.test(password);
      const hasSpecial = /[!@#$%^&*()\-_=+{};:,<.>]/.test(password);

      // calculamos puntaje
      if (hasLength) score++;
      if (hasUppercase) score++;
      if (hasNumber) score++;
      if (hasSpecial) score++;

      // Determinar fortaleza segun el puntaje
      if (score === 4) {
        strength = 'fuerte';
        color = '#28a745';
      } else if (score === 3) {
        strength = 'buena';
        color = '#17a2b8';
      } else if (score === 2) {
        strength = 'media';
        color = '#ffc107';
      } else {
        strength = 'débil';
        color = '#dc3545';
      }

      return {
        score,
        strength,
        color,
        hasLength,
        hasUppercase,
        hasNumber,
        hasSpecial
      };
    }

    function updateRequirementsUI(result) {
      document.getElementById('reqLength').className = result.hasLength ? 'requirement-valid' : 'requirement-invalid';
      document.getElementById('reqUppercase').className = result.hasUppercase ? 'requirement-valid' : 'requirement-invalid';
      document.getElementById('reqNumber').className = result.hasNumber ? 'requirement-valid' : 'requirement-invalid';
      document.getElementById('reqSpecial').className = result.hasSpecial ? 'requirement-valid' : 'requirement-invalid';

      const icons = document.querySelectorAll('#passwordRequirements li i');
      const requirements = [result.hasLength, result.hasUppercase, result.hasNumber, result.hasSpecial];

      icons.forEach((icon, index) => {
        icon.className = requirements[index] ? 'fas fa-check-circle' : 'fas fa-circle';
      });

      const strengthBar = document.getElementById('passwordStrengthBar');
      const strengthText = document.getElementById('passwordStrengthText');

      strengthBar.className = 'password-strength strength-' + result.strength;
      strengthBar.style.backgroundColor = result.color;
      strengthText.innerHTML = `Fortaleza: <strong>${result.strength}</strong>`;
      strengthText.style.color = result.color;
    }

    document.addEventListener('DOMContentLoaded', function() {
      const password = document.getElementById('password');
      const passwordRepeat = document.getElementById('password_repeat');
      const passwordMatchText = document.getElementById('passwordMatchText');
      const resetForm = document.getElementById('resetForm');

      // Solo ejecutamos si los elementos estan
      if (password) {
        // validamos la fortaleza de la contraseña
        password.addEventListener('input', function() {
          const passwordValue = this.value;

          if (passwordValue.length > 0) {
            const result = checkPasswordStrength(passwordValue);
            updateRequirementsUI(result);
          } else {

            const strengthBar = document.getElementById('passwordStrengthBar');
            const strengthText = document.getElementById('passwordStrengthText');

            if (strengthBar) {
              strengthBar.className = 'password-strength';
              strengthBar.style.backgroundColor = '#e9ecef';
            }
            if (strengthText) {
              strengthText.textContent = 'Fortaleza: ';
            }
          }

          // verificamos la coincidencia de ambas contraseñas
          if (passwordRepeat && passwordRepeat.value) {
            checkPasswordMatch();
          }
        });
      }

      function checkPasswordMatch() {
        if (password && passwordRepeat && password.value && passwordRepeat.value) {
          if (password.value === passwordRepeat.value) {
            passwordMatchText.innerHTML = '<span class="text-success">✓ Las contraseñas coinciden</span>';
            return true;
          } else {
            passwordMatchText.innerHTML = '<span class="text-danger">✗ Las contraseñas no coinciden</span>';
            return false;
          }
        }
        if (passwordMatchText) {
          passwordMatchText.textContent = 'Las contraseñas deben coincidir';
        }
        return false;
      }

      if (passwordRepeat) {
        passwordRepeat.addEventListener('input', checkPasswordMatch);
      }

      // validamos antes del envio
      if (resetForm) {
        resetForm.addEventListener('submit', function(e) {
          if (password) {
            const passwordValue = password.value;
            const result = checkPasswordStrength(passwordValue);

            // validamos los requisitos minimos
            if (!result.hasLength || !result.hasUppercase || !result.hasNumber || !result.hasSpecial) {
              e.preventDefault();
              alert('La contraseña debe cumplir con todos los requisitos:\n\n' +
                '• Al menos 8 caracteres\n' +
                '• Al menos una letra mayúscula\n' +
                '• Al menos un número\n' +
                '• Al menos un símbolo (!@#$%^&*)');
              return;
            }
          }

          // validamos la coincidencia de la contraseña
          if (!checkPasswordMatch()) {
            e.preventDefault();
            alert('Por favor, asegúrate de que las contraseñas coincidan.');
          }
        });
      }
    });
  </script>
</body>

</html>