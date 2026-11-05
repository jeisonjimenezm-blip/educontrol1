/* =========================================================
   EduControl - Interacción en el navegador
   ========================================================= */
document.addEventListener('DOMContentLoaded', () => {

  // ---- Filtro de la oferta por tipo (cursos, técnicas, auxiliares) ----
  const botones = document.querySelectorAll('.filtro');
  const tarjetas = document.querySelectorAll('.tarjeta');
  botones.forEach(boton => {
    boton.addEventListener('click', () => {
      botones.forEach(b => b.classList.remove('activo'));
      boton.classList.add('activo');
      const filtro = boton.dataset.filtro;
      tarjetas.forEach(t => {
        t.classList.toggle('oculta', filtro !== 'todos' && t.dataset.tipo !== filtro);
      });
    });
  });

  // ---- "Inscribirme" en una tarjeta: selecciona el programa sin recargar ----
  const selectPrograma = document.querySelector('select[name="id_programa"]');
  document.querySelectorAll('[data-programa]').forEach(enlace => {
    enlace.addEventListener('click', ev => {
      if (!selectPrograma) return;
      ev.preventDefault();
      selectPrograma.value = enlace.dataset.programa;
      document.getElementById('inscripcion').scrollIntoView({ behavior: 'smooth' });
      selectPrograma.focus();
    });
  });

  // ---- Validación del formulario antes de enviarlo ----
  const form = document.getElementById('formInscripcion');
  if (!form) return;

  // Solo números en documento y celular
  ['documento', 'celular'].forEach(nombre => {
    form[nombre].addEventListener('input', e => { e.target.value = e.target.value.replace(/\D/g, ''); });
  });

  const reglas = {
    id_programa: v => v !== '' || 'Selecciona un programa.',
    documento:   v => /^\d{6,15}$/.test(v) || 'Entre 6 y 15 dígitos.',
    nombres:     v => v.trim().length >= 2 || 'Escribe tus nombres.',
    apellidos:   v => v.trim().length >= 2 || 'Escribe tus apellidos.',
    celular:     v => /^3\d{9}$/.test(v) || '10 dígitos y debe empezar por 3.',
    correo:      v => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) || 'Correo no válido.',
  };

  function validarCampo(nombre) {
    const campo = form[nombre];
    const resultado = reglas[nombre](campo.value);
    const contenedor = campo.closest('label');
    let aviso = contenedor.querySelector('.error-campo');
    if (resultado === true) {
      campo.classList.remove('invalido');
      if (aviso) aviso.remove();
      return true;
    }
    campo.classList.add('invalido');
    if (!aviso) {
      aviso = document.createElement('span');
      aviso.className = 'error-campo';
      contenedor.appendChild(aviso);
    }
    aviso.textContent = resultado;
    return false;
  }

  Object.keys(reglas).forEach(n => form[n].addEventListener('blur', () => validarCampo(n)));

  form.addEventListener('submit', ev => {
    let ok = true;
    Object.keys(reglas).forEach(n => { if (!validarCampo(n)) ok = false; });
    if (!form.autoriza_datos.checked) {
      ok = false;
      alert('Debes autorizar el tratamiento de datos personales para continuar.');
    }
    if (!ok) {
      ev.preventDefault();
      const primero = form.querySelector('.invalido');
      if (primero) primero.focus();
      return;
    }
    const boton = form.querySelector('button[type="submit"]');
    boton.disabled = true;
    boton.textContent = 'Enviando...';
  });
});
