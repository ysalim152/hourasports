// assets/js/common-passwords.js
// Liste de mots de passe courants pour empêcher leur utilisation.
// Cette liste peut être étendue. Pour un environnement de production,
// envisagez de charger une liste beaucoup plus grande ou d'utiliser un service comme "Have I Been Pwned".

const COMMON_PASSWORDS = new Set([
  '123456', '12345678', '123456789', 'password', 'azerty', 'qwerty',
  'azertyuiop', '111111', 'football', 'iloveyou', 'admin', 'root', 'welcome',
  'sunshine', 'soleil', 'azerty123', '12345', '1234567', 'test', 'login',
  'master', 'user', 'guest', 'secret', 'dragon', 'shadow', 'hunter2',
  'qwertyuiop', 'p@ssword', '123soleil', 'marseille', 'paris', 'alger',
  'blida', 'asclub'
]);