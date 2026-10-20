Carpeta para el video de la Práctica 7 (Incrustación de un video).

Coloca aquí tu video con el nombre exacto:

    mi_video.mp4

La página p7.php lo incrusta así:

    <video controls poster="assets/foto.jpg">
        <source src="assets/video/mi_video.mp4" type="video/mp4">
        <source src="assets/video/mi_video.webm" type="video/webm">
    </video>

Si usas otro nombre o formato, cambia el valor de $video y el atributo
type="" dentro de p7.php. Los archivos actuales (mi_video.mp4 y
mi_video.webm) son videos de ejemplo que puedes reemplazar por el tuyo.
