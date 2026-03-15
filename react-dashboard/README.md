# Profesor Dashboard (React + Tailwind)

Pequeño scaffold para visualizar una versión moderna y accesible del Dashboard de Profesor.

Requisitos:
- Node.js 18+ y npm

Instalación y ejecución local:

```bash
cd react-dashboard
npm install
npm run dev
```

Esto levantará una pequeña app Vite en `http://localhost:5173` por defecto.

Notas de diseño:
- Contraste: textos oscuros en fondos claros; tablas usan fondo claro para legibilidad.
- Espaciado: escala de 8px (Tailwind usa 4px base; se usan múltiplos para respetar la escala).
- Accesibilidad: estados `focus` visibles y botones secundarios como ghost buttons para reducir competencia visual.
