import { Button } from "@/Components/ui/button";
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from "@/Components/ui/command";
import { Label } from "@/Components/ui/label";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/Components/ui/popover";
import { cn } from "@/lib/utils";
import { Check, ChevronsUpDown, Plus } from "lucide-react";
import { useMemo, useState } from "react";

export default function SourceCombobox({
    sources = [],
    value,
    onValueChange,
    label,
    placeholder = "Selecciona una fuente...",
    searchPlaceholder = "Buscar fuente...",
    emptyMessage = "No se encontraron fuentes",
    onCreateNew,
    createNewText = "Nueva Fuente",
    helperText,
    error,
    disabled = false,
    className,
}) {
    const [open, setOpen] = useState(false);

    // Agrupar sources por campaign si tienen campaign_id
    const groupedSources = useMemo(() => {
        if (!sources) return {};
        return sources.reduce((acc, source) => {
            const campaignId = source.campaign_id || "sin_campaign";
            const campaignName = source.campaign?.name || "Sin Campaña";

            if (!acc[campaignId]) {
                acc[campaignId] = {
                    campaignId,
                    campaignName,
                    sources: [],
                };
            }

            acc[campaignId].sources.push(source);
            return acc;
        }, {});
    }, [sources]);

    // Ordenar grupos: primero "Sin Campaña", luego por nombre de campaña
    const sortedGroups = useMemo(() => {
        return Object.values(groupedSources).sort((a, b) => {
            if (a.campaignName === "Sin Campaña") return 1;
            if (b.campaignName === "Sin Campaña") return -1;
            return a.campaignName.localeCompare(b.campaignName);
        });
    }, [groupedSources]);

    // Encontrar el source seleccionado
    const selectedSource = useMemo(() => {
        return sources.find(
            (source) => source.id.toString() === value?.toString()
        );
    }, [sources, value]);

    const isDisabled = disabled || sources.length === 0;

    return (
        <div className={cn("space-y-2", className)}>
            {(label || onCreateNew) && (
                <div className="flex items-center justify-between">
                    {label && <Label>{label}</Label>}
                    {onCreateNew && (
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={onCreateNew}
                            className="h-7 text-xs text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50"
                        >
                            <Plus className="mr-1 h-3 w-3" />
                            {createNewText}
                        </Button>
                    )}
                </div>
            )}

            <Popover open={open} onOpenChange={setOpen}>
                <PopoverTrigger asChild>
                    <Button
                        variant="outline"
                        role="combobox"
                        aria-expanded={open}
                        className={cn(
                            "w-full justify-between bg-white px-3 py-2 h-10", // Explicit height and padding
                            !selectedSource && "text-muted-foreground",
                            isDisabled && "opacity-50 cursor-not-allowed",
                            error && "border-red-500 ring-red-500"
                        )}
                        disabled={isDisabled}
                    >
                        {selectedSource ? (
                            <span className="truncate flex items-center gap-2 text-left">
                                <span className="font-medium truncate">
                                    {selectedSource.name}
                                </span>
                                {selectedSource.client?.name && (
                                    <span className="text-xs text-gray-500 font-normal truncate">
                                        ({selectedSource.client.name})
                                    </span>
                                )}
                            </span>
                        ) : (
                            <span>
                                {sources.length === 0
                                    ? "No hay fuentes disponibles"
                                    : placeholder}
                            </span>
                        )}
                        <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent
                    className="w-[var(--radix-popover-trigger-width)] p-0"
                    align="start"
                >
                    <Command>
                        <CommandInput
                            placeholder={searchPlaceholder}
                            className="h-9 focus:ring-0 focus:border-none border-none ring-0 focus-visible:ring-0 shadow-none outline-none"
                        />
                        <CommandList>
                            <CommandEmpty>{emptyMessage}</CommandEmpty>
                            {sortedGroups.map((group) => (
                                <CommandGroup
                                    key={group.campaignId}
                                    heading={group.campaignName}
                                >
                                    {group.sources.map((source) => (
                                        <CommandItem
                                            key={source.id}
                                            value={`${source.name} ${source.id}`}
                                            onSelect={() => {
                                                // Trigger change
                                                onValueChange(
                                                    source.id.toString()
                                                );
                                                setOpen(false);
                                            }}
                                        >
                                            <Check
                                                className={cn(
                                                    "mr-2 h-4 w-4",
                                                    value?.toString() ===
                                                        source.id.toString()
                                                        ? "opacity-100"
                                                        : "opacity-0"
                                                )}
                                            />
                                            <div className="flex flex-col">
                                                <span className="font-medium">
                                                    {source.name}
                                                </span>
                                                {source.client?.name && (
                                                    <span className="text-xs text-muted-foreground">
                                                        {source.client.name}
                                                    </span>
                                                )}
                                            </div>
                                        </CommandItem>
                                    ))}
                                </CommandGroup>
                            ))}
                        </CommandList>
                    </Command>
                </PopoverContent>
            </Popover>

            {helperText && (
                <p className="text-xs text-muted-foreground">{helperText}</p>
            )}
            {error && <p className="text-sm text-red-500">{error}</p>}
        </div>
    );
}
