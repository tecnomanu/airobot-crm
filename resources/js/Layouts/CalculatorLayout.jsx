import { Toaster } from "@/Components/ui/sonner";

export default function CalculatorLayout({ children, toolbar }) {
    return (
        <div className="flex flex-col h-screen w-full bg-white">
            {/* Header con toolbar */}
            <header className="border-b border-gray-300 bg-white">
                {toolbar}
            </header>

            {/* Contenido principal sin padding */}
            <main className="flex-1 overflow-hidden">{children}</main>

            <Toaster richColors position="bottom-right" />
        </div>
    );
}
