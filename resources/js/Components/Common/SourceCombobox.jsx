import { Button } from "@/Components/ui/button";
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from "@/Components/ui/command";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/Components/ui/popover";
import { cn } from "@/lib/utils";
import { Check, ChevronsUpDown } from "lucide-react";
import { useState } from "react";

export default function SourceCombobox({
    sources = [],
    value,
    onValueChange,
    placeholder = "Seleccionar fuente...",
    emptyMessage = "No se encontraron fuentes",
    className,
    disabled = false,
}) {
    const [open, setOpen] = useState(false);

    // Agrupar sources por campaign si tienen campaign_id
    const groupedSources = sources.reduce((acc, source) => {
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

    // Ordenar grupos: primero "Sin Campaña", luego por nombre de campaña
    const sortedGroups = Object.values(groupedSources).sort((a, b) => {
        if (a.campaignName === "Sin Campaña") return 1;
        if (b.campaignName === "Sin Campaña") return -1;
        return a.campaignName.localeCompare(b.campaignName);
    });

    // Encontrar el source seleccionado
    const selectedSource = sources.find(
        (source) => source.id.toString() === value?.toString()
    );

    const isDisabled = disabled || sources.length === 0;

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    className={cn(
                        "w-full justify-between",
                        !selectedSource && "text-muted-foreground",
                        isDisabled && "opacity-50 cursor-not-allowed",
                        className
                    )}
                    disabled={isDisabled}
                >
                    {selectedSource ? (
                        <span className="truncate">
                            <span className="font-medium">
                                {selectedSource.name}
                            </span>
                            {selectedSource.client?.name && (
                                <span className="text-xs text-gray-400 font-normal ml-1">
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
                        placeholder="Buscar fuente..."
                        className="focus:ring-0 focus:ring-offset-0 focus-visible:ring-0 focus-visible:ring-offset-0"
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
                                        value={`${source.name}-${source.id}`}
                                        onSelect={() => {
                                            onValueChange(source.id.toString());
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
                                        <span className="truncate">
                                            <span className="font-medium">
                                                {source.name}
                                            </span>
                                            {source.client?.name && (
                                                <span className="text-xs text-gray-400 font-normal ml-1">
                                                    ({source.client.name})
                                                </span>
                                            )}
                                        </span>
                                    </CommandItem>
                                ))}
                            </CommandGroup>
                        ))}
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}
