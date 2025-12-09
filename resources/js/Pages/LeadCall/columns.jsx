import { ArrowUpDown, Eye } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { Badge } from "@/components/ui/badge";
import { router } from "@inertiajs/react";

export const getCallHistoryColumns = () => [
    {
        id: "select",
        header: ({ table }) => (
            <Checkbox
                checked={table.getIsAllPageRowsSelected()}
                onCheckedChange={(value) => table.toggleAllPageRowsSelected(!!value)}
                aria-label="Seleccionar todo"
            />
        ),
        cell: ({ row }) => (
            <Checkbox
                checked={row.getIsSelected()}
                onCheckedChange={(value) => row.toggleSelected(!!value)}
                aria-label="Seleccionar fila"
            />
        ),
        enableSorting: false,
        enableHiding: false,
    },
    {
        accessorKey: "phone",
        header: ({ column }) => {
            return (
                <Button
                    variant="ghost"
                    onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
                >
                    Teléfono
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
        cell: ({ row }) => <div className="font-medium">{row.getValue("phone")}</div>,
    },
    {
        accessorKey: "campaign",
        header: "Campaña",
        cell: ({ row }) => <div>{row.original.campaign?.name || "-"}</div>,
        enableSorting: false,
    },
    {
        accessorKey: "client",
        header: "Cliente",
        cell: ({ row }) => <div>{row.original.client?.name || "-"}</div>,
        enableSorting: false,
    },
    {
        accessorKey: "call_date",
        header: ({ column }) => {
            return (
                <Button
                    variant="ghost"
                    onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
                >
                    Fecha
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
        cell: ({ row }) => (
            <div>
                {new Date(row.getValue("call_date")).toLocaleString("es-ES", {
                    dateStyle: "short",
                    timeStyle: "short",
                })}
            </div>
        ),
    },
    {
        accessorKey: "duration_seconds",
        header: "Duración",
        cell: ({ row }) => {
            const seconds = row.getValue("duration_seconds");
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = seconds % 60;
            return <div>{`${minutes}m ${remainingSeconds}s`}</div>;
        },
    },
    {
        accessorKey: "status",
        header: "Estado",
        cell: ({ row }) => {
            const status = row.getValue("status");
            const colors = {
                completed: "bg-green-100 text-green-800 hover:bg-green-100",
                no_answer: "bg-yellow-100 text-yellow-800 hover:bg-yellow-100",
                hung_up: "bg-orange-100 text-orange-800 hover:bg-orange-100",
                failed: "bg-red-100 text-red-800 hover:bg-red-100",
                busy: "bg-purple-100 text-purple-800 hover:bg-purple-100",
            };
            return (
                <Badge className={colors[status] || "bg-gray-100 text-gray-800"}>
                    {row.original.status_label}
                </Badge>
            );
        },
    },
    {
        accessorKey: "cost",
        header: () => <div className="text-right">Costo</div>,
        cell: ({ row }) => {
            const cost = parseFloat(row.getValue("cost"));
            const formatted = new Intl.NumberFormat("es-ES", {
                style: "currency",
                currency: "EUR",
            }).format(cost);
            return <div className="text-right font-medium">{formatted}</div>;
        },
    },
    {
        id: "actions",
        enableHiding: false,
        cell: ({ row }) => {
            const call = row.original;
            return (
                <div className="flex justify-end gap-2">
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() =>
                            router.visit(route("call-history.show", call.id))
                        }
                    >
                        <Eye className="h-4 w-4" />
                    </Button>
                </div>
            );
        },
    },
];

