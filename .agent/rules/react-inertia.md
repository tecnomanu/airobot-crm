---
trigger: always_on
---

---
globs: resources/js/**/*.jsx,resources/js/**/*.js,resources/js/**/*.tsx,resources/js/**/*.ts
alwaysApply: false
description: Arquitectura Frontend React + Inertia.js
---

# Arquitectura Frontend React + Inertia.js

## Stack Overview

Este proyecto usa **React con Inertia.js** para el frontend, proporcionando una experiencia SPA moderna mientras mantiene el routing y controladores server-side de Laravel.

### Tecnologías Core

-   **React 18**: Librería de componentes
-   **Inertia.js**: Puente Laravel-React (no se necesitan endpoints API)
-   **Tailwind CSS**: Framework CSS utility-first
-   **shadcn/ui**: Componentes React de alta calidad construidos sobre Radix UI
-   **Vite**: Herramienta de build y servidor de desarrollo

## Arquitectura y Organización de Componentes

### Estructura de Directorios

```
resources/js/
├── Components/
│   ├── ui/                    # Componentes base del design system de shadcn/ui
│   │   ├── button.jsx
│   │   ├── card.jsx
│   │   ├── dialog.jsx
│   │   └── ...
│   ├── common/               # Componentes reutilizables complejos
│   │   ├── Modal.jsx
│   │   ├── DataTable.jsx
│   │   ├── StatusBadge.jsx
│   │   └── Avatar.jsx
│   └── [module]/            # Componentes específicos del dominio
│       ├── example/          # Componentes relacionados con ejemplo
│       └── ...
├── Pages/                   # Componentes de página de Inertia.js
│   ├── Admin/
│   ├── Auth/
│   └── Dashboard.jsx
├── Layouts/                 # Componentes de layout
├── hooks/                   # Custom React hooks
└── lib/                    # Funciones utilitarias
```

### Reglas de Clasificación de Componentes

#### 1. **UI Components** (`@/Components/ui/`)

-   **Propósito**: Componentes base del design system de shadcn/ui
-   **Instalación**: Siempre usa `pnpm dlx shadcn@latest add [component-name]`
-   **Ejemplos**: `button`, `card`, `dialog`, `input`, `table`
-   **Nunca modifiques**: Estos son mantenidos por shadcn/ui

```bash
# Instalando nuevos componentes shadcn/ui
pnpm dlx shadcn@latest add dialog
pnpm dlx shadcn@latest add table
pnpm dlx shadcn@latest add form
```

#### 2. **Common Components** (`@/Components/common/`)

-   **Propósito**: Componentes reutilizables complejos que extienden componentes ui
-   **Características**: Usados a través de múltiples módulos/dominios
-   **Ejemplos**: `DataTable`, `Modal`, `StatusBadge`, `Avatar`

```jsx
// Ejemplo: StatusBadge extendiendo ui/badge
import { Badge } from "@/Components/ui/badge";

export default function StatusBadge({ status, variant, children }) {
    return <Badge variant={variant}>{children || status}</Badge>;
}
```

#### 3. **Domain Components** (`@/Components/[module]/`)

-   **Propósito**: Componentes con lógica de negocio específica del módulo
-   **Organización**: Agrupa por dominio de negocio
-   **Ejemplos**: `ExampleHistory`, `ExampleDetails`

```jsx
// Ejemplo: Componente específico del dominio
// Archivo: @/Components/example/ExampleHistory.jsx
import { Card } from "@/Components/ui/card";
import { Badge } from "@/Components/ui/badge";

export default function ExampleHistory({ example }) {
    // Lógica específica del dominio aquí
}
```

## Principios SOLID en React

### Single Responsibility Principle (SRP)

-   **Un componente = Una responsabilidad**
-   Divide componentes grandes en más pequeños y enfocados
-   Separa preocupaciones: lógica UI vs lógica de negocio vs data fetching

```jsx
// ❌ INCORRECTO: Componente haciendo demasiado
function ExampleShow() {
    // 500+ líneas de preocupaciones mezcladas
}

// ✅ CORRECTO: Preocupaciones separadas
function ExampleShow() {
    return (
        <div>
            <ExampleHeader example={example} />
            <ExampleTabs example={example} />
            <ExampleSection items={example.items} />
        </div>
    );
}
```

### Open/Closed Principle (OCP)

-   **Extiende componentes vía props y composición**
-   Usa render props y children para flexibilidad

```jsx
// ✅ CORRECTO: Componente extensible
function DataTable({ columns, data, renderRow, filters }) {
    return <Table>{data.map((item, index) => renderRow(item, index))}</Table>;
}
```

### Dependency Inversion Principle (DIP)

-   **Depende de abstracciones (props/hooks) no implementaciones concretas**
-   Usa custom hooks para data fetching y lógica de negocio

```jsx
// ✅ CORRECTO: Componente depende de abstracción (hook)
function ExampleList() {
    const { examples, loading, error } = useExamples();

    if (loading) return <LoadingSpinner />;
    if (error) return <ErrorMessage error={error} />;

    return <ExampleGrid examples={examples} />;
}
```

## Guías Específicas de Inertia.js

### Componentes de Página

-   **Ubicación**: `resources/js/Pages/`
-   **Propósito**: Componentes de nivel superior renderizados por controladores Laravel
-   **Props**: Reciben datos de controladores Laravel vía Inertia

```jsx
// Archivo: resources/js/Pages/Admin/Examples/Show.jsx
export default function ExampleShow({ auth, example }) {
    // auth y example vienen del controlador Laravel
    return (
        <AuthenticatedLayout user={auth.user}>
            <ExampleDetails example={example} />
        </AuthenticatedLayout>
    );
}
```

### Navegación

-   Usa componente `Link` de `@inertiajs/react`
-   Usa `router` para navegación programática

```jsx
import { Link, router } from "@inertiajs/react";

// Navegación declarativa
<Link href={route("admin.examples.show", example.id)}>Ver Detalles</Link>;

// Navegación programática
router.visit(route("admin.dashboard"));
```

### Manejo de Formularios

-   Usa hook `useForm` de Inertia.js
-   Maneja errores de validación de Laravel

```jsx
import { useForm } from "@inertiajs/react";

function EditForm({ example }) {
    const { data, setData, patch, processing, errors } = useForm({
        name: example.name,
        description: example.description,
    });

    const submit = (e) => {
        e.preventDefault();
        patch(route("admin.examples.update", example.id));
    };

    return (
        <form onSubmit={submit}>
            <Input
                value={data.name}
                onChange={(e) => setData("name", e.target.value)}
                error={errors.name}
            />
        </form>
    );
}
```

## Guías de Estilo

### Mejores Prácticas de Tailwind CSS

-   **Usa clases utility**: Prefiere `className="flex items-center space-x-2"` sobre CSS personalizado
-   **Variantes de componentes**: Usa clases condicionales para diferentes estados
-   **Diseño responsivo**: Enfoque mobile-first con prefijos `sm:`, `md:`, `lg:`

```jsx
// ✅ CORRECTO: Clases utility con variantes
<button
    className={cn(
        "px-4 py-2 rounded-md font-medium transition-colors",
        variant === "primary" && "bg-blue-600 text-white hover:bg-blue-700",
        variant === "secondary" &&
            "bg-gray-200 text-gray-900 hover:bg-gray-300",
        size === "sm" && "px-3 py-1 text-sm",
        disabled && "opacity-50 cursor-not-allowed"
    )}
>
    {children}
</button>
```

### Integración shadcn/ui

-   **Nunca modifiques** componentes ui directamente
-   **Extiende vía composición** cuando sea necesario
-   **Usa CSS variables** para theming (ya configurado)

```jsx
// ✅ CORRECTO: Extendiendo componentes shadcn/ui
import { Button } from "@/Components/ui/button";

function LoadingButton({ loading, children, ...props }) {
    return (
        <Button disabled={loading} {...props}>
            {loading && <Spinner className="mr-2 h-4 w-4" />}
            {children}
        </Button>
    );
}
```

## Límites de Tamaño y Complejidad

### Límites de Tamaño de Componentes

-   **Máximo 200 líneas** por archivo de componente
-   **Máximo 10 props** por componente
-   **Divide** componentes complejos en más pequeños

### Cuándo Dividir Componentes

```jsx
// ❌ INCORRECTO: Componente de 500+ líneas
function ExampleShow() {
    // Demasiada lógica, demasiadas preocupaciones
}

// ✅ CORRECTO: Dividido en componentes enfocados
function ExampleShow({ example }) {
    return (
        <div>
            <ExampleHeader example={example} />
            <ExampleTabs example={example} />
        </div>
    );
}

function ExampleHeader({ example }) {
    // Enfocado solo en preocupaciones del header
}

function ExampleTabs({ example }) {
    return (
        <Tabs>
            <ExampleTab items={example.items} />
            <DetailsTab details={example.details} />
        </Tabs>
    );
}
```

## Patrón de Custom Hooks

### Hooks de Data Fetching

```jsx
// Archivo: resources/js/hooks/useExamples.js
export function useExamples(exampleId) {
    const [examples, setExamples] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        // Lógica de fetch aquí
    }, [exampleId]);

    return { examples, loading, refetch: () => {} };
}
```

### Hooks de Lógica de Negocio

```jsx
// Archivo: resources/js/hooks/useStatusInfo.js
export function useStatusInfo() {
    const getExampleStatusInfo = (status, expiresAt) => {
        // Lógica de cálculo de estado
        return { text, variant, color };
    };

    return { getExampleStatusInfo };
}
```

## Checklist de Prevención de Errores

### Antes de Crear Componentes

1. ✅ Verifica si existe componente shadcn/ui primero
2. ✅ Determina carpeta correcta (`ui/`, `common/`, o `[module]/`)
3. ✅ Asegura responsabilidad única
4. ✅ Planifica interfaz de props
5. ✅ Considera reutilización

### Antes de Enviar PR

1. ✅ Archivo de componente < 200 líneas
2. ✅ Interfaz de props es limpia y enfocada
3. ✅ TypeScript/PropTypes apropiado si se usa
4. ✅ Diseño responsivo testeado
5. ✅ Consideraciones de accesibilidad
