# 📦 Install Dependencies

This guide covers how to install the Composer packages used by the project: **Ratchet** (WebSockets), **PHPMailer** (Email), **mPDF** (PDF generation), and **PhpSpreadsheet** (Excel/spreadsheet handling). They are all optional — only install what your project needs.

---

## ✅ Prerequisites

### 1. Install Composer

Composer is PHP's dependency manager and is required to install these packages.

- Download and install from [getcomposer.org](https://getcomposer.org/)
- Verify the installation by running:

```bash
composer --version
```

You should see something like `Composer version 2.x.x`.

---

## 📁 Project Setup

### 2. Navigate to the Composer Directory

Open your terminal or command prompt and navigate to the `libs/composer/` folder — this is the root directory for all Composer dependencies in this project, and where your `composer.json` file lives (or will be created):

```bash
cd /path/to/your/project/libs/composer
```

---

## 🔧 Installing the Packages

### 3. Install the Composer Packages

Run the following command to install all supported packages and their dependencies in one step:

```bash
composer require cboden/ratchet phpmailer/phpmailer mpdf/mpdf phpoffice/phpspreadsheet
```

> 💡 You can also install them separately if you only need one:
> ```bash
> # Ratchet only
> composer require cboden/ratchet
>
> # PHPMailer only
> composer require phpmailer/phpmailer
>
> # mPDF only
> composer require mpdf/mpdf
>
> # PhpSpreadsheet only
> composer require phpoffice/phpspreadsheet
> ```

Once complete, Composer will:
- Create or update your `composer.json` with the new dependencies
- Generate a `composer.lock` file to lock dependency versions
- Download all packages into a `vendor/` directory

The installed packages will be located in:
```
libs/composer/vendor/
```

---

## ✔️ Verify Installation

### 4. Confirm the Packages Are Installed

After installation, you should see the following in your project:

```
libs/composer/
├── vendor/
│   ├── cboden/         # Ratchet
│   ├── phpmailer/      # PHPMailer
│   ├── mpdf/           # mPDF
│   ├── phpoffice/      # PhpSpreadsheet
│   └── autoload.php    # Composer autoloader
├── composer.json
└── composer.lock
```

You can also verify via the terminal (run from `libs/composer/`):

```bash
cd libs/composer
composer show
```

This will list all installed packages and their versions.

---

## 🔗 Using the Packages in PHP

### 5. Include the Autoloader

At the top of any PHP file where you want to use Ratchet, PHPMailer, mPDF, or PhpSpreadsheet, include the Composer autoload file:

```php
require 'libs/composer/vendor/autoload.php';
```

### PHPMailer — Basic Usage Example

```php
require 'libs/composer/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.example.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'your@email.com';
    $mail->Password   = 'yourpassword';
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    $mail->setFrom('from@example.com', 'Your Name');
    $mail->addAddress('recipient@example.com');
    $mail->Subject = 'Hello!';
    $mail->Body    = 'This is a test email.';

    $mail->send();
    echo 'Email sent successfully.';
} catch (Exception $e) {
    echo "Email failed: {$mail->ErrorInfo}";
}
```

### Ratchet — Basic WebSocket Server Example

```php
require 'libs/composer/vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class Chat implements MessageComponentInterface {
    public function onOpen(ConnectionInterface $conn) {
        echo "New connection: {$conn->resourceId}\n";
    }
    public function onMessage(ConnectionInterface $from, $msg) {
        echo "Message received: {$msg}\n";
    }
    public function onClose(ConnectionInterface $conn) {
        echo "Connection closed: {$conn->resourceId}\n";
    }
    public function onError(ConnectionInterface $conn, \Exception $e) {
        $conn->close();
    }
}

$server = IoServer::factory(
    new HttpServer(new WsServer(new Chat())),
    8080
);

$server->run();
```

### PhpSpreadsheet — Basic Spreadsheet Example

```php
require 'libs/composer/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'Hello');
$sheet->setCellValue('B1', 'World');

$writer = new Xlsx($spreadsheet);
$writer->save('example.xlsx');
```

### mPDF — Basic PDF Example

```php
require 'libs/composer/vendor/autoload.php';

$mpdf = new \Mpdf\Mpdf();
$mpdf->WriteHTML('<h1>Hello PDF</h1>');
$mpdf->Output('example.pdf', 'D');
```

To start the WebSocket server, run:

```bash
php server.php
```

---

## 🔄 Updating Dependencies

To update your installed packages to their latest compatible versions, run from `libs/composer/`:

```bash
cd libs/composer
composer update
```

---

## ❓ Troubleshooting

| Issue | Solution |
|---|---|
| `composer` command not found | Ensure Composer is added to your system PATH |
| `vendor/` folder missing | Run `composer install` to reinstall from `composer.lock` |
| Class not found errors | Make sure `autoload.php` is included at the top of your PHP file |
| Ratchet server won't start | Check that port `8080` is not already in use |
| PHPMailer SMTP error | Verify your SMTP credentials and that port 587 is open |
| Spreadsheet export fails | Confirm `phpoffice/phpspreadsheet` is installed and the file is writable |