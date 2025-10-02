const btn = document.getElementById('btnFetch');
const apiMessage = document.getElementById('apiMessage');

async function fetchData() {
  try {
    const response = await fetch('http://localhost/tp-entornos-graficos-backend/api/test.php');
    const data = await response.json();
    apiMessage.textContent = `Mensaje: ${data.mensaje} - Fecha: ${data.fecha}`;
  } catch (error) {
    apiMessage.textContent = 'Error al conectar con la API';
    console.error(error);
  }
}

fetch(url) //con esto solicitamos datos desde el front al back
  .then(response => response.json())
  .then(data => {
    console.log(data); // Ver los datos en la consola
    // Aquí puedes recorrer 'data' y mostrarlos en HTML (los trae en JSON)
  })
  .catch(error => console.error('Error:', error));

// Cargar mensaje al inicio
fetchData();

btn.addEventListener('click', fetchData);
