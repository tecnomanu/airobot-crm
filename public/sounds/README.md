# Sonidos de Notificación

Este directorio contiene los archivos de audio para las notificaciones del sistema.

## Archivo Requerido

Coloca tu archivo de sonido de notificación aquí con el nombre:

```
notification.mp3
```

## Dónde Conseguir Sonidos Gratuitos

### Opciones Recomendadas:

1. **Freesound.org** (requiere registro gratuito)

    - https://freesound.org/search/?q=notification
    - Busca: "notification", "ding", "bell", "chime"

2. **Zapsplat** (gratuito)

    - https://www.zapsplat.com/sound-effect-categories/notification-sounds/
    - Descarga directa, sin registro

3. **Mixkit** (gratuito, sin registro)

    - https://mixkit.co/free-sound-effects/notification/
    - Sonidos de alta calidad

4. **Pixabay** (gratuito)
    - https://pixabay.com/sound-effects/search/notification/
    - Licencia libre

### Características Recomendadas:

-   **Duración**: 0.5 - 2 segundos
-   **Formato**: MP3 (mejor compatibilidad)
-   **Volumen**: Moderado (se ajusta automáticamente al 50%)
-   **Tono**: Agradable, no intrusivo

## Sonido por Defecto Sugerido

Si no sabes cuál elegir, busca en Freesound:

-   "notification tone"
-   Filtra por duración: < 2 segundos
-   Ordena por popularidad

## Estructura de Archivos

```
public/sounds/
├── notification.mp3      # Sonido principal (REQUERIDO)
├── lead-new.mp3         # Opcional: sonido específico para leads nuevos
├── lead-updated.mp3     # Opcional: sonido para actualizaciones
└── README.md            # Este archivo
```

## Sonidos Personalizados por Evento

Puedes agregar sonidos personalizados y usarlos en el código:

```javascript
// En cualquier componente
notifications.show({
    title: "Título",
    body: "Mensaje",
    soundUrl: "/sounds/custom-sound.mp3",
});
```

## Testing

Para probar el sonido:

1. Abre la consola del navegador
2. Ejecuta:

```javascript
const audio = new Audio("/sounds/notification.mp3");
audio.play();
```

## Licencias

Asegúrate de que el sonido que uses tenga licencia apropiada para uso comercial si aplica.
Los sitios recomendados arriba ofrecen sonidos con licencias libres.
