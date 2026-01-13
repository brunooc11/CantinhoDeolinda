const signUpButton = document.getElementById('signUp');
const signInButton = document.getElementById('signIn');
const container = document.getElementById('container');

signUpButton.addEventListener('click', () => {
  container.classList.add("right-panel-active");
});

signInButton.addEventListener('click', () => {
  container.classList.remove("right-panel-active");
});

/* 👁 Mostrar / esconder password */
document.querySelectorAll('.toggle-pass').forEach(btn => {
  btn.addEventListener('click', () => {

    const input = document.getElementById(btn.dataset.target);
    const eyeOpen = btn.querySelector('.eye-open');
    const eyeClosed = btn.querySelector('.eye-closed');

    const hidden = input.type === 'password';

    input.type = hidden ? 'text' : 'password';
    eyeOpen.style.display = hidden ? 'none' : 'block';
    eyeClosed.style.display = hidden ? 'block' : 'none';
  });
});
