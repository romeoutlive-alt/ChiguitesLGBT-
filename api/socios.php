<?php
/**
 * Recepción de solicitudes de alta de socio/a/e.
 *
 * La web es estática (HTML prerenderizado). El formulario de /hazte-socio
 * genera la ficha y el carné en PDF dentro del navegador y envía ambos
 * archivos aquí junto con los datos personales.
 *
 * Requiere PHP 8 y la función mail() habilitada (Alwaysdata la incluye).
 * Ajusta RECIPIENT y MAIL_FROM_* si cambian los datos de la asociación.
 */

declare(strict_types=1);

// Las fechas del correo se muestran en hora peninsular.
date_default_timezone_set('Europe/Madrid');

const RECIPIENT = 'chiguitxs.lgtbplus@gmail.com';
const RECIPIENT_NAME = 'Chiguitxs LGBT+ Palencia';

// Remitente. Debe ser un buzón real del dominio alojado para que SPF y DKIM
// validen: por defecto web@<dominio del sitio>.
const MAIL_FROM_LOCAL = 'web';
const MAIL_FROM_NAME = 'Web Chiguites';

const MAX_FILE_BYTES = 8 * 1024 * 1024; // 8 MB por PDF
const MAX_REQUESTS_PER_HOUR = 10;

// Campos de texto que se incluyen en el correo. La clave es el nombre del
// campo en el formulario y el valor la etiqueta que se muestra en el mensaje.
const TEXT_FIELDS = [
    'nombre' => 'Nombre y apellidos',
    'pronombres' => 'Pronombres',
    'dni' => 'DNI / NIE',
    'nacimiento' => 'Fecha de nacimiento',
    'domicilio' => 'Domicilio',
    'codigo-postal' => 'Código postal',
    'localidad' => 'Localidad',
    'telefono' => 'Teléfono',
    'email' => 'Correo electrónico',
    'imagen' => 'Autorización de uso de imagen',
    'grupo-whatsapp' => 'Autorización de grupo de WhatsApp',
    'privacidad' => 'Consentimiento de protección de datos',
    'firma' => 'Firma escrita',
];

const REQUIRED_FIELDS = ['nombre', 'email', 'privacidad', 'firma'];

/**
 * Responde con JSON y termina la ejecución.
 */
function respond(int $status, array $payload): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Dirección del remitente: web@<dominio del sitio>.
 */
function sender_address(): string
{
    $host = $_SERVER['SERVER_NAME'] ?? '';
    if ($host === '' || !preg_match('/^[a-z0-9.-]+$/i', $host)) {
        $host = 'localhost';
    }
    // El buzón suele ser web@dominio, no web@www.dominio.
    $host = preg_replace('/^www\./i', '', $host);

    return MAIL_FROM_LOCAL . '@' . $host;
}

/**
 * Comprueba que un archivo subido es un PDF real y devuelve sus datos.
 *
 * @return array{name: string, data: string}
 */
function read_pdf(string $field): array
{
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
        respond(422, ['ok' => false, 'error' => 'Falta el archivo ' . $field . '.']);
    }

    $file = $_FILES[$field];
    $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($error !== UPLOAD_ERR_OK) {
        respond(422, ['ok' => false, 'error' => 'No se pudo recibir el archivo ' . $field . '.']);
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        respond(422, ['ok' => false, 'error' => 'Archivo no válido: ' . $field . '.']);
    }

    if (($file['size'] ?? 0) > MAX_FILE_BYTES) {
        respond(413, ['ok' => false, 'error' => 'El archivo ' . $field . ' es demasiado grande.']);
    }

    $data = file_get_contents($file['tmp_name']);
    if ($data === false || strncmp($data, '%PDF-', 5) !== 0) {
        respond(422, ['ok' => false, 'error' => 'El archivo ' . $field . ' no es un PDF.']);
    }

    $name = basename((string) ($file['name'] ?? ($field . '.pdf')));
    $name = preg_replace('/[^A-Za-z0-9._-]/', '-', $name) ?: ($field . '.pdf');

    return ['name' => $name, 'data' => $data];
}

/**
 * Límite sencillo por IP para evitar envíos masivos. Si el directorio
 * temporal no es escribible, se omite el límite en lugar de fallar.
 */
function enforce_rate_limit(): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'desconocida';
    $path = sys_get_temp_dir() . '/chiguites-socios-' . hash('sha256', $ip) . '.txt';
    $now = time();
    $hits = [];

    if (is_readable($path)) {
        $raw = file_get_contents($path);
        $stored = $raw === false ? [] : (json_decode($raw, true) ?: []);
        $hits = array_values(array_filter(
            is_array($stored) ? $stored : [],
            static fn ($time): bool => is_int($time) && $time > $now - 3600,
        ));
    }

    if (count($hits) >= MAX_REQUESTS_PER_HOUR) {
        respond(429, ['ok' => false, 'error' => 'Demasiadas solicitudes. Inténtalo más tarde.']);
    }

    $hits[] = $now;
    @file_put_contents($path, json_encode($hits), LOCK_EX);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    respond(405, ['ok' => false, 'error' => 'Método no permitido.']);
}

// Honeypot: el campo está oculto, así que un humano nunca lo rellena.
if (trim((string) ($_POST['bot-field'] ?? '')) !== '') {
    respond(400, ['ok' => false, 'error' => 'Solicitud no válida.']);
}

$missing = [];
foreach (REQUIRED_FIELDS as $field) {
    if (trim((string) ($_POST[$field] ?? '')) === '') {
        $missing[] = $field;
    }
}

if ($missing !== []) {
    respond(422, ['ok' => false, 'error' => 'Faltan campos obligatorios: ' . implode(', ', $missing) . '.']);
}

$email = trim((string) $_POST['email']);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(422, ['ok' => false, 'error' => 'El correo electrónico no es válido.']);
}

enforce_rate_limit();

$application = read_pdf('ficha-pdf');
$card = read_pdf('carne-pdf');

$lines = [];
foreach (TEXT_FIELDS as $field => $label) {
    $value = trim((string) ($_POST[$field] ?? ''));
    $lines[] = $label . ': ' . ($value === '' ? '(no indicado)' : $value);
}

$plainBody = "Nueva solicitud de alta de socio/a/e recibida desde la web.\n\n"
    . implode("\n", $lines)
    . "\n\nSe adjuntan la ficha completa y el carné en PDF.\n"
    . "Fecha del envío: " . date('d/m/Y H:i') . "\n";
$plainBody = str_replace("\n", "\r\n", $plainBody);

// Cuerpo multipart/mixed: texto + dos PDF en base64.
$boundary = 'chiguites-' . bin2hex(random_bytes(16));
$body = "--{$boundary}\r\n"
    . "Content-Type: text/plain; charset=UTF-8\r\n"
    . "Content-Transfer-Encoding: 8bit\r\n\r\n"
    . $plainBody . "\r\n";

foreach ([$application, $card] as $file) {
    $body .= "--{$boundary}\r\n"
        . 'Content-Type: application/pdf; name="' . $file['name'] . "\"\r\n"
        . "Content-Transfer-Encoding: base64\r\n"
        . 'Content-Disposition: attachment; filename="' . $file['name'] . "\"\r\n\r\n"
        . rtrim(chunk_split(base64_encode($file['data']), 76, "\r\n"), "\r\n")
        . "\r\n";
}

$body .= "--{$boundary}--\r\n";

$fromAddress = sender_address();
$fromName = '=?UTF-8?B?' . base64_encode(MAIL_FROM_NAME) . '?=';
$subject = '=?UTF-8?B?' . base64_encode('Nueva solicitud de socio/a/e · Chiguitxs LGBT+ Palencia') . '?=';

$headers = [
    'From: ' . $fromName . ' <' . $fromAddress . '>',
    'Reply-To: ' . $email,
    'MIME-Version: 1.0',
    'Content-Type: multipart/mixed; boundary="' . $boundary . '"',
    'X-Mailer: PHP/' . phpversion(),
];

$sent = @mail(
    RECIPIENT_NAME . ' <' . RECIPIENT . '>',
    $subject,
    $body,
    implode("\r\n", $headers),
);

if (!$sent) {
    respond(500, ['ok' => false, 'error' => 'No se pudo enviar el correo.']);
}

respond(200, ['ok' => true]);
