const signUpButton = document.getElementById('signUp');
const signInButton = document.getElementById('signIn');
const container = document.getElementById('container');

signUpButton.addEventListener('click', () => {
  container.classList.add("right-panel-active");
});

signInButton.addEventListener('click', () => {
  container.classList.remove("right-panel-active");
});

/* Mostrar / esconder password */
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

/* Indicativo + bandeira */
const codigoPaisInput = document.getElementById('codigoPaisInput');
const countryFlag = document.getElementById('countryFlag');
const telefoneInput = document.getElementById('telefoneInput');
const fallbackFlagIconUrl = 'https://flagcdn.com/w20/un.png';

const countryFlagMap = {
  '+351': 'pt',
  '+34': 'es',
  '+33': 'fr',
  '+49': 'de',
  '+44': 'gb',
  '+1': 'us',
  '+55': 'br',
  '+244': 'ao',
  '+258': 'mz',
  '+238': 'cv'
};

const phoneLengthRules = {
  '+351': { min: 9, max: 9 },
  '+34': { min: 9, max: 9 },
  '+33': { min: 9, max: 9 },
  '+49': { min: 10, max: 11 },
  '+44': { min: 10, max: 10 },
  '+1': { min: 10, max: 10 },
  '+55': { min: 10, max: 11 },
  '+244': { min: 9, max: 9 },
  '+258': { min: 9, max: 9 },
  '+238': { min: 7, max: 7 }
};

function updateCountryFlag() {
  if (!codigoPaisInput || !countryFlag) return;

  let value = codigoPaisInput.value.trim();
  value = value.replace(/[^\d+]/g, '');

  if (value && !value.startsWith('+')) {
    value = `+${value.replace(/\+/g, '')}`;
  }

  if (value.startsWith('+')) {
    const digits = value.slice(1).replace(/\D/g, '').slice(0, 4);
    value = `+${digits}`;
  }

  codigoPaisInput.value = value;
  if (countryFlag) {
    const iso2 = countryFlagMap[value];
    if (iso2) {
      countryFlag.src = `https://flagcdn.com/w20/${iso2}.png`;
      countryFlag.alt = `Bandeira ${value}`;
    } else {
      countryFlag.src = fallbackFlagIconUrl;
      countryFlag.alt = 'Selecionar pais';
    }
  }

  if (telefoneInput) {
    const rule = phoneLengthRules[value] || { min: 4, max: 14 };
    telefoneInput.minLength = rule.min;
    telefoneInput.maxLength = rule.max;
    telefoneInput.pattern = `[0-9]{${rule.min},${rule.max}}`;
    telefoneInput.title = `Numero local: ${rule.min} a ${rule.max} digitos`;
    telefoneInput.placeholder = `Numero (${rule.min}-${rule.max} digitos)`;

    const digits = telefoneInput.value.replace(/\D/g, '');
    telefoneInput.value = digits.slice(0, rule.max);
  }
}

if (codigoPaisInput) {
  codigoPaisInput.addEventListener('input', updateCountryFlag);
  codigoPaisInput.addEventListener('change', updateCountryFlag);
  updateCountryFlag();
}

if (telefoneInput) {
  telefoneInput.addEventListener('input', () => {
    const code = codigoPaisInput ? codigoPaisInput.value : '';
    const rule = phoneLengthRules[code] || { min: 4, max: 14 };
    const digits = telefoneInput.value.replace(/\D/g, '');
    telefoneInput.value = digits.slice(0, rule.max);
  });
}
