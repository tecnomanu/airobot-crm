import { Toaster } from "@/components/ui/sonner";

export default function ExcelLayout({ children, toolbar }) {
    return (
        <div className="flex flex-col h-screen w-full bg-white">
            {/* Header con toolbar */}
            <header className="border-b border-gray-300 bg-white">
                {toolbar}
            </header>

            {/* Contenido principal sin padding */}
            <main className="flex-1 overflow-hidden">{children}</main>

            <Toaster richColors position="top-right" />
        </div>
    );
}
