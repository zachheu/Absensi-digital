import { Html5Qrcode, Html5QrcodeSupportedFormats } from 'html5-qrcode';

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

const showMessage = (element, message, variant = 'info') => {
    if (!element) {
        return;
    }

    element.textContent = message;
    element.className = `rounded-lg border px-4 py-3 text-sm ${
        variant === 'success'
            ? 'border-army-300 bg-army-100 text-army-900'
            : variant === 'error'
              ? 'border-red-200 bg-red-50 text-red-800'
              : 'border-cream-300 bg-cream-100 text-army-700'
    }`;
};

export const initScanner = ({ readerId, messageId, endpoint }) => {
    const reader = document.getElementById(readerId);
    const message = document.getElementById(messageId);

    if (!reader) {
        return;
    }

    if (!navigator.mediaDevices?.getUserMedia) {
        showMessage(
            message,
            'Kamera tidak tersedia di perangkat atau browser ini. Gunakan input token manual di bawah.',
            'error',
        );
        return;
    }

    let scanning = false;

    const scanner = new Html5Qrcode(readerId, {
        formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE],
        verbose: false,
    });

    const submitToken = async (token) => {
        showMessage(message, 'Memvalidasi QR ke server...');

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({ token }),
            });

            const data = await response.json();

            if (data.successful) {
                showMessage(
                    message,
                    `${data.message} ${data.activity ? `Kegiatan: ${data.activity}.` : ''}`,
                    'success',
                );
            } else {
                showMessage(message, data.message ?? 'Absensi gagal.', 'error');
            }
        } catch (error) {
            showMessage(message, 'Gagal menghubungi server. Periksa koneksi lalu coba lagi.', 'error');
        }
    };

    const start = async () => {
        try {
            await scanner.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 240, height: 240 } },
                async (decodedText) => {
                    if (scanning) {
                        return;
                    }

                    scanning = true;
                    await submitToken(decodedText.trim());

                    try {
                        await scanner.pause(true);
                    } catch (error) {
                        // scanner mungkin sudah berhenti
                    }

                    window.setTimeout(() => {
                        scanning = false;
                        scanner.resume().catch(() => {});
                    }, 3000);
                },
                () => {},
            );

            showMessage(message, 'Kamera aktif. Arahkan ke QR Code pada layar admin.');
        } catch (error) {
            showMessage(
                message,
                'Tidak dapat mengakses kamera. Pastikan izin kamera diberikan, lalu muat ulang halaman.',
                'error',
            );
        }
    };

    start();

    window.addEventListener('pagehide', () => {
        scanner.stop().catch(() => {});
    });
};
