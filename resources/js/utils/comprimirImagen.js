/**
 * Reduce una foto antes de subirla: el celular las toma de 3–5 MB y en
 * pantalla se ven igual a ~1920 px en JPEG al 80 % (~300–600 KB). Ahorra
 * espacio en la nube (10 GB gratis), descarga diaria y datos móviles.
 *
 * Solo toca JPG/PNG/WEBP de más de 400 KB. Si algo falla (navegador viejo,
 * imagen dañada) o el resultado no pesa menos, regresa el archivo original:
 * nunca impide subir la evidencia.
 */
const LADO_MAXIMO = 1920;
const CALIDAD = 0.8;
const PESO_MINIMO = 400 * 1024;
const TIPOS = ['image/jpeg', 'image/png', 'image/webp'];

const cargar = async (archivo) => {
    if (typeof createImageBitmap === 'function') {
        // Respeta la orientación de la cámara (EXIF) para que no salga girada.
        return createImageBitmap(archivo, { imageOrientation: 'from-image' });
    }
    const url = URL.createObjectURL(archivo);
    try {
        const img = new Image();
        img.src = url;
        await img.decode();
        return img;
    } finally {
        URL.revokeObjectURL(url);
    }
};

export async function comprimirImagen(archivo) {
    if (!(archivo instanceof Blob) || !TIPOS.includes(archivo.type) || archivo.size < PESO_MINIMO) {
        return archivo;
    }

    try {
        const imagen = await cargar(archivo);
        const escala = Math.min(1, LADO_MAXIMO / Math.max(imagen.width, imagen.height));
        const ancho = Math.round(imagen.width * escala);
        const alto = Math.round(imagen.height * escala);

        const canvas = document.createElement('canvas');
        canvas.width = ancho;
        canvas.height = alto;
        const ctx = canvas.getContext('2d');
        // Fondo blanco: un PNG con transparencia no debe quedar negro en JPEG.
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, ancho, alto);
        ctx.drawImage(imagen, 0, 0, ancho, alto);
        imagen.close?.();

        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', CALIDAD));
        if (!blob || blob.size >= archivo.size) return archivo;

        const nombre = archivo.name.replace(/\.[^.]+$/, '') + '.jpg';
        return new File([blob], nombre, { type: 'image/jpeg', lastModified: archivo.lastModified });
    } catch {
        return archivo;
    }
}
