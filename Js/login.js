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
  '+1': 'us',
  '+7': 'ru',
  '+20': 'eg',
  '+27': 'za',
  '+30': 'gr',
  '+31': 'nl',
  '+32': 'be',
  '+33': 'fr',
  '+34': 'es',
  '+36': 'hu',
  '+39': 'it',
  '+40': 'ro',
  '+41': 'ch',
  '+43': 'at',
  '+44': 'gb',
  '+45': 'dk',
  '+46': 'se',
  '+47': 'no',
  '+48': 'pl',
  '+49': 'de',
  '+51': 'pe',
  '+52': 'mx',
  '+53': 'cu',
  '+54': 'ar',
  '+55': 'br',
  '+56': 'cl',
  '+57': 'co',
  '+58': 've',
  '+60': 'my',
  '+61': 'au',
  '+62': 'id',
  '+63': 'ph',
  '+64': 'nz',
  '+65': 'sg',
  '+66': 'th',
  '+81': 'jp',
  '+82': 'kr',
  '+84': 'vn',
  '+86': 'cn',
  '+90': 'tr',
  '+91': 'in',
  '+92': 'pk',
  '+93': 'af',
  '+94': 'lk',
  '+95': 'mm',
  '+98': 'ir',
  '+212': 'ma',
  '+213': 'dz',
  '+216': 'tn',
  '+218': 'ly',
  '+220': 'gm',
  '+221': 'sn',
  '+222': 'mr',
  '+223': 'ml',
  '+224': 'gn',
  '+225': 'ci',
  '+226': 'bf',
  '+228': 'tg',
  '+229': 'bj',
  '+230': 'mu',
  '+231': 'lr',
  '+232': 'sl',
  '+233': 'gh',
  '+234': 'ng',
  '+235': 'td',
  '+236': 'cf',
  '+237': 'cm',
  '+238': 'cv',
  '+239': 'st',
  '+240': 'gq',
  '+241': 'ga',
  '+242': 'cg',
  '+243': 'cd',
  '+244': 'ao',
  '+245': 'gw',
  '+249': 'sd',
  '+250': 'rw',
  '+251': 'et',
  '+252': 'so',
  '+253': 'dj',
  '+254': 'ke',
  '+255': 'tz',
  '+256': 'ug',
  '+257': 'bi',
  '+258': 'mz',
  '+260': 'zm',
  '+261': 'mg',
  '+263': 'zw',
  '+264': 'na',
  '+267': 'bw',
  '+268': 'sz',
  '+269': 'km',
  '+291': 'er',
  '+351': 'pt',
  '+352': 'lu',
  '+353': 'ie',
  '+354': 'is',
  '+355': 'al',
  '+356': 'mt',
  '+357': 'cy',
  '+358': 'fi',
  '+359': 'bg',
  '+370': 'lt',
  '+371': 'lv',
  '+372': 'ee',
  '+373': 'md',
  '+374': 'am',
  '+375': 'by',
  '+376': 'ad',
  '+377': 'mc',
  '+378': 'sm',
  '+380': 'ua',
  '+381': 'rs',
  '+382': 'me',
  '+385': 'hr',
  '+386': 'si',
  '+387': 'ba',
  '+389': 'mk',
  '+420': 'cz',
  '+421': 'sk',
  '+423': 'li',
  '+502': 'gt',
  '+503': 'sv',
  '+504': 'hn',
  '+505': 'ni',
  '+506': 'cr',
  '+507': 'pa',
  '+509': 'ht',
  '+591': 'bo',
  '+592': 'gy',
  '+593': 'ec',
  '+595': 'py',
  '+597': 'sr',
  '+598': 'uy',
  '+670': 'tl',
  '+673': 'bn',
  '+675': 'pg',
  '+679': 'fj',
  '+685': 'ws',
  '+850': 'kp',
  '+855': 'kh',
  '+856': 'la',
  '+880': 'bd',
  '+886': 'tw',
  '+960': 'mv',
  '+961': 'lb',
  '+962': 'jo',
  '+963': 'sy',
  '+964': 'iq',
  '+965': 'kw',
  '+966': 'sa',
  '+967': 'ye',
  '+968': 'om',
  '+971': 'ae',
  '+972': 'il',
  '+974': 'qa',
  '+975': 'bt',
  '+976': 'mn',
  '+977': 'np',
  '+994': 'az',
  '+995': 'ge'
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
  '+238': { min: 7, max: 7 },
  '+245': { min: 7, max: 7 },
  '+239': { min: 7, max: 7 },
  '+670': { min: 7, max: 8 },
  '+39': { min: 9, max: 10 },
  '+31': { min: 9, max: 9 },
  '+32': { min: 8, max: 9 },
  '+41': { min: 9, max: 9 },
  '+43': { min: 10, max: 13 },
  '+352': { min: 9, max: 9 },
  '+353': { min: 9, max: 9 },
  '+52': { min: 10, max: 10 },
  '+54': { min: 10, max: 11 },
  '+56': { min: 9, max: 9 },
  '+57': { min: 10, max: 10 },
  '+58': { min: 10, max: 10 },
  '+51': { min: 9, max: 9 },
  '+61': { min: 9, max: 9 },
  '+27': { min: 9, max: 9 }
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
