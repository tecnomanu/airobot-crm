import { Avatar, AvatarFallback } from "@/Components/ui/avatar";
import { Button } from "@/Components/ui/button";
import { Kbd, KbdGroup } from "@/Components/ui/kbd";
import {
    Menubar,
    MenubarContent,
    MenubarItem,
    MenubarMenu,
    MenubarSeparator,
    MenubarShortcut,
    MenubarTrigger,
} from "@/Components/ui/menubar";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/Components/ui/popover";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import { Separator } from "@/Components/ui/separator";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/Components/ui/tooltip";
import { useExcelFormat } from "@/hooks/useExcelFormat";
import { Link } from "@inertiajs/react";
import {
    AlignCenter,
    AlignLeft,
    AlignRight,
    ArrowLeft,
    Bold,
    Clipboard,
    Copy,
    Download,
    File,
    FileText,
    Italic,
    LogOut,
    Palette,
    Redo,
    Settings,
    Trash2,
    Type,
    Underline,
    Undo,
    Upload,
} from "lucide-react";
import { useRef } from "react";

export default function ExcelToolbar({
    onNew,
    onOpen,
    onSave,
    onExportCSV,
    onImportCSV,
    onUndo,
    onRedo,
    canUndo = false,
    canRedo = false,
    onCopy,
    onPaste,
    onDelete,
    selectedFormat = {},
    onFormatChange,
    onBackToMenu,
    user = null,
}) {
    const fileInputRef = useRef(null);
    const { colorPalette } = useExcelFormat();

    const getUserInitials = (name) => {
        if (!name) return "U";
        return name
            .split(" ")
            .map((n) => n[0])
            .join("")
            .toUpperCase()
            .substring(0, 2);
    };

    const handleImportClick = () => {
        fileInputRef.current?.click();
    };

    const handleFileChange = (e) => {
        const file = e.target.files?.[0];
        if (file && onImportCSV) {
            onImportCSV(file);
        }
        // Reset input
        if (fileInputRef.current) {
            fileInputRef.current.value = "";
        }
    };

    const handleFormatUpdate = (updates) => {
        if (onFormatChange) {
            onFormatChange({
                ...selectedFormat,
                ...updates,
            });
        }
    };

    return (
        <TooltipProvider>
            <div className="flex flex-col border-b border-gray-300 bg-white">
                {/* Primera fila: Botón Volver, Archivo y Editar */}
                <div className="flex items-center gap-1 px-2 py-1">
                    {/* Botón Volver */}
                    {onBackToMenu && (
                        <>
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={onBackToMenu}
                                        className="h-8"
                                    >
                                        <ArrowLeft className="h-4 w-4 mr-2" />
                                        Volver
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <div className="flex items-center gap-2">
                                        Volver al Dashboard
                                    </div>
                                </TooltipContent>
                            </Tooltip>
                            <Separator orientation="vertical" className="h-6" />
                        </>
                    )}

                    {/* Menú Archivo */}
                    <Menubar>
                        <MenubarMenu>
                            <MenubarTrigger>Archivo</MenubarTrigger>
                            <MenubarContent>
                                <MenubarItem onClick={onNew}>
                                    <File className="mr-2 h-4 w-4" />
                                    Nuevo
                                    <MenubarShortcut>
                                        <KbdGroup>
                                            <Kbd>Ctrl</Kbd>
                                            <Kbd>N</Kbd>
                                        </KbdGroup>
                                    </MenubarShortcut>
                                </MenubarItem>
                                <MenubarItem onClick={onOpen}>
                                    <FileText className="mr-2 h-4 w-4" />
                                    Abrir
                                    <MenubarShortcut>
                                        <KbdGroup>
                                            <Kbd>Ctrl</Kbd>
                                            <Kbd>O</Kbd>
                                        </KbdGroup>
                                    </MenubarShortcut>
                                </MenubarItem>
                                <MenubarItem onClick={onSave}>
                                    <FileText className="mr-2 h-4 w-4" />
                                    Guardar
                                    <MenubarShortcut>
                                        <KbdGroup>
                                            <Kbd>Ctrl</Kbd>
                                            <Kbd>S</Kbd>
                                        </KbdGroup>
                                    </MenubarShortcut>
                                </MenubarItem>
                                <MenubarSeparator />
                                <MenubarItem onClick={onExportCSV}>
                                    <Download className="mr-2 h-4 w-4" />
                                    Exportar CSV
                                </MenubarItem>
                                <MenubarItem onClick={handleImportClick}>
                                    <Upload className="mr-2 h-4 w-4" />
                                    Importar CSV
                                </MenubarItem>
                            </MenubarContent>
                        </MenubarMenu>
                    </Menubar>

                    <input
                        ref={fileInputRef}
                        type="file"
                        accept=".csv"
                        onChange={handleFileChange}
                        className="hidden"
                    />

                    <Separator orientation="vertical" className="h-6" />

                    {/* Menú Editar */}
                    <Menubar>
                        <MenubarMenu>
                            <MenubarTrigger>Editar</MenubarTrigger>
                            <MenubarContent>
                                <MenubarItem
                                    onClick={onUndo}
                                    disabled={!canUndo}
                                >
                                    <Undo className="mr-2 h-4 w-4" />
                                    Deshacer
                                    <MenubarShortcut>
                                        <KbdGroup>
                                            <Kbd>Ctrl</Kbd>
                                            <Kbd>Z</Kbd>
                                        </KbdGroup>
                                    </MenubarShortcut>
                                </MenubarItem>
                                <MenubarItem
                                    onClick={onRedo}
                                    disabled={!canRedo}
                                >
                                    <Redo className="mr-2 h-4 w-4" />
                                    Rehacer
                                    <MenubarShortcut>
                                        <KbdGroup>
                                            <Kbd>Ctrl</Kbd>
                                            <Kbd>Y</Kbd>
                                        </KbdGroup>
                                    </MenubarShortcut>
                                </MenubarItem>
                                <MenubarSeparator />
                                <MenubarItem onClick={onCopy}>
                                    <Copy className="mr-2 h-4 w-4" />
                                    Copiar
                                    <MenubarShortcut>
                                        <KbdGroup>
                                            <Kbd>Ctrl</Kbd>
                                            <Kbd>C</Kbd>
                                        </KbdGroup>
                                    </MenubarShortcut>
                                </MenubarItem>
                                <MenubarItem onClick={onPaste}>
                                    <Clipboard className="mr-2 h-4 w-4" />
                                    Pegar
                                    <MenubarShortcut>
                                        <KbdGroup>
                                            <Kbd>Ctrl</Kbd>
                                            <Kbd>V</Kbd>
                                        </KbdGroup>
                                    </MenubarShortcut>
                                </MenubarItem>
                                <MenubarSeparator />
                                <MenubarItem onClick={onDelete}>
                                    <Trash2 className="mr-2 h-4 w-4" />
                                    Eliminar
                                    <MenubarShortcut>
                                        <Kbd>Del</Kbd>
                                    </MenubarShortcut>
                                </MenubarItem>
                            </MenubarContent>
                        </MenubarMenu>
                    </Menubar>

                    <div className="flex-1" />

                    {/* Menú Usuario */}
                    {user && (
                        <Menubar>
                            <MenubarMenu>
                                <MenubarTrigger className="flex items-center gap-2">
                                    <Avatar className="h-6 w-6">
                                        <AvatarFallback className="bg-black text-white text-xs font-semibold">
                                            {getUserInitials(user.name)}
                                        </AvatarFallback>
                                    </Avatar>
                                    <span className="text-sm font-medium">
                                        {user.name}
                                    </span>
                                </MenubarTrigger>
                                <MenubarContent align="end">
                                    <MenubarItem disabled>
                                        <span className="font-semibold">
                                            Mi Cuenta
                                        </span>
                                    </MenubarItem>
                                    <MenubarSeparator />
                                    <MenubarItem asChild>
                                        <Link
                                            href={route("profile.edit")}
                                            className="flex items-center cursor-pointer"
                                        >
                                            <Settings className="mr-2 h-4 w-4" />
                                            <span>Configuración</span>
                                        </Link>
                                    </MenubarItem>
                                    <MenubarSeparator />
                                    <MenubarItem asChild>
                                        <Link
                                            href={route("logout")}
                                            method="post"
                                            as="button"
                                            className="w-full flex items-center cursor-pointer"
                                        >
                                            <LogOut className="mr-2 h-4 w-4" />
                                            <span>Cerrar Sesión</span>
                                        </Link>
                                    </MenubarItem>
                                </MenubarContent>
                            </MenubarMenu>
                        </Menubar>
                    )}
                </div>

                {/* Segunda fila: Barra de edición/formato */}
                <div className="flex items-center gap-1 px-2 py-1 border-t border-gray-200">
                    {/* Fuente */}
                    <Select
                        value={selectedFormat.fontFamily || "Arial"}
                        onValueChange={(value) =>
                            handleFormatUpdate({ fontFamily: value })
                        }
                    >
                        <SelectTrigger className="w-[120px] h-8">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="Arial">Arial</SelectItem>
                            <SelectItem value="Times New Roman">
                                Times New Roman
                            </SelectItem>
                            <SelectItem value="Courier New">
                                Courier New
                            </SelectItem>
                            <SelectItem value="Verdana">Verdana</SelectItem>
                            <SelectItem value="Georgia">Georgia</SelectItem>
                        </SelectContent>
                    </Select>

                    {/* Tamaño de fuente */}
                    <Select
                        value={String(selectedFormat.fontSize || 12)}
                        onValueChange={(value) =>
                            handleFormatUpdate({ fontSize: parseInt(value) })
                        }
                    >
                        <SelectTrigger className="w-[70px] h-8">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {[
                                8, 9, 10, 11, 12, 14, 16, 18, 20, 24, 28, 32,
                                36, 48,
                            ].map((size) => (
                                <SelectItem key={size} value={String(size)}>
                                    {size}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    {/* Estilos de texto */}
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <Button
                                variant={
                                    selectedFormat.bold ? "default" : "ghost"
                                }
                                size="icon"
                                className="h-8 w-8"
                                onClick={() =>
                                    handleFormatUpdate({
                                        bold: !selectedFormat.bold,
                                    })
                                }
                            >
                                <Bold className="h-4 w-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <div className="flex items-center gap-2">
                                Negrita{" "}
                                <KbdGroup>
                                    <Kbd>Ctrl</Kbd>
                                    <Kbd>B</Kbd>
                                </KbdGroup>
                            </div>
                        </TooltipContent>
                    </Tooltip>

                    <Tooltip>
                        <TooltipTrigger asChild>
                            <Button
                                variant={
                                    selectedFormat.italic ? "default" : "ghost"
                                }
                                size="icon"
                                className="h-8 w-8"
                                onClick={() =>
                                    handleFormatUpdate({
                                        italic: !selectedFormat.italic,
                                    })
                                }
                            >
                                <Italic className="h-4 w-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <div className="flex items-center gap-2">
                                Cursiva{" "}
                                <KbdGroup>
                                    <Kbd>Ctrl</Kbd>
                                    <Kbd>I</Kbd>
                                </KbdGroup>
                            </div>
                        </TooltipContent>
                    </Tooltip>

                    <Tooltip>
                        <TooltipTrigger asChild>
                            <Button
                                variant={
                                    selectedFormat.underline
                                        ? "default"
                                        : "ghost"
                                }
                                size="icon"
                                className="h-8 w-8"
                                onClick={() =>
                                    handleFormatUpdate({
                                        underline: !selectedFormat.underline,
                                    })
                                }
                            >
                                <Underline className="h-4 w-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <div className="flex items-center gap-2">
                                Subrayado{" "}
                                <KbdGroup>
                                    <Kbd>Ctrl</Kbd>
                                    <Kbd>U</Kbd>
                                </KbdGroup>
                            </div>
                        </TooltipContent>
                    </Tooltip>

                    <Separator orientation="vertical" className="h-6 mx-1" />

                    {/* Alineación */}
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <Button
                                variant={
                                    selectedFormat.align === "left"
                                        ? "default"
                                        : "ghost"
                                }
                                size="icon"
                                className="h-8 w-8"
                                onClick={() =>
                                    handleFormatUpdate({ align: "left" })
                                }
                            >
                                <AlignLeft className="h-4 w-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Alinear a la izquierda</TooltipContent>
                    </Tooltip>

                    <Tooltip>
                        <TooltipTrigger asChild>
                            <Button
                                variant={
                                    selectedFormat.align === "center"
                                        ? "default"
                                        : "ghost"
                                }
                                size="icon"
                                className="h-8 w-8"
                                onClick={() =>
                                    handleFormatUpdate({ align: "center" })
                                }
                            >
                                <AlignCenter className="h-4 w-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Centrar</TooltipContent>
                    </Tooltip>

                    <Tooltip>
                        <TooltipTrigger asChild>
                            <Button
                                variant={
                                    selectedFormat.align === "right"
                                        ? "default"
                                        : "ghost"
                                }
                                size="icon"
                                className="h-8 w-8"
                                onClick={() =>
                                    handleFormatUpdate({ align: "right" })
                                }
                            >
                                <AlignRight className="h-4 w-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Alinear a la derecha</TooltipContent>
                    </Tooltip>

                    <Separator orientation="vertical" className="h-6 mx-1" />

                    {/* Color de texto */}
                    <Popover>
                        <PopoverTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="h-8 w-8"
                            >
                                <Type
                                    className="h-4 w-4"
                                    style={{
                                        color:
                                            selectedFormat.textColor ||
                                            "#000000",
                                    }}
                                />
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent className="w-64">
                            <div className="grid grid-cols-8 gap-2">
                                {colorPalette.map((color) => (
                                    <button
                                        key={color}
                                        className="w-8 h-8 rounded border border-gray-300 hover:scale-110 transition-transform"
                                        style={{ backgroundColor: color }}
                                        onClick={() =>
                                            handleFormatUpdate({
                                                textColor: color,
                                            })
                                        }
                                    />
                                ))}
                            </div>
                        </PopoverContent>
                    </Popover>

                    {/* Color de fondo */}
                    <Popover>
                        <PopoverTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="h-8 w-8"
                            >
                                <Palette className="h-4 w-4" />
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent className="w-64">
                            <div className="grid grid-cols-8 gap-2">
                                {colorPalette.map((color) => (
                                    <button
                                        key={color}
                                        className="w-8 h-8 rounded border border-gray-300 hover:scale-110 transition-transform"
                                        style={{ backgroundColor: color }}
                                        onClick={() =>
                                            handleFormatUpdate({
                                                backgroundColor: color,
                                            })
                                        }
                                    />
                                ))}
                            </div>
                        </PopoverContent>
                    </Popover>
                </div>
            </div>
        </TooltipProvider>
    );
}
