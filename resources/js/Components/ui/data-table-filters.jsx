import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import { Search, X } from "lucide-react";

/**
 * Unified DataTable Filters Component
 *
 * Provides a consistent filtering interface for all DataTables
 *
 * @param {Object} props
 * @param {Array} props.filters - Array of filter configurations
 * @param {Object} props.values - Current filter values
 * @param {Function} props.onChange - Handler for filter changes
 * @param {Function} props.onClear - Handler for clearing filters
 * @param {Function} props.onSearch - Optional search submit handler
 */
export default function DataTableFilters({
    filters = [],
    values = {},
    onChange,
    onClear,
    onSearch,
}) {
    const handleSearch = (e) => {
        e.preventDefault();
        if (onSearch) {
            onSearch(e);
        }
    };

    const renderFilter = (filter) => {
        switch (filter.type) {
            case "search":
                return (
                    <form
                        key={filter.name}
                        onSubmit={handleSearch}
                        className="flex-1 max-w-md relative"
                    >
                        <Input
                            placeholder={filter.placeholder || "Buscar..."}
                            value={values[filter.name] || ""}
                            onChange={(e) =>
                                onChange(filter.name, e.target.value)
                            }
                            className="pl-4 pr-10 py-2.5 bg-indigo-50 border-0 rounded-lg"
                        />
                        <Button
                            type="submit"
                            size="icon"
                            variant="ghost"
                            className="absolute right-1 top-1/2 -translate-y-1/2 h-8 w-8"
                        >
                            <Search className="h-4 w-4 text-gray-400" />
                        </Button>
                    </form>
                );

            case "select":
                return (
                    <Select
                        key={filter.name}
                        value={values[filter.name] || "all"}
                        onValueChange={(value) =>
                            onChange(filter.name, value === "all" ? "" : value)
                        }
                    >
                        <SelectTrigger
                            className={filter.className || "w-[180px]"}
                        >
                            <SelectValue
                                placeholder={
                                    filter.placeholder || "Seleccionar"
                                }
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">
                                {filter.allLabel || "Todos"}
                            </SelectItem>
                            {filter.options?.map((option) => (
                                <SelectItem
                                    key={option.value || option.id}
                                    value={
                                        option.value?.toString() ||
                                        option.id?.toString()
                                    }
                                >
                                    {option.label || option.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                );

            default:
                return null;
        }
    };

    return (
        <div className="flex flex-wrap items-center gap-3">
            {filters.map((filter) => renderFilter(filter))}

            {onClear && (
                <Button variant="outline" onClick={onClear} size="sm">
                    <X className="mr-2 h-4 w-4" />
                    Limpiar
                </Button>
            )}
        </div>
    );
}
