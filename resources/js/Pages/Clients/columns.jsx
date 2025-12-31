import StatusSwitch from "@/Components/Common/StatusSwitch";
import { Button } from "@/Components/ui/button";
import { Checkbox } from "@/Components/ui/checkbox";
import { router } from "@inertiajs/react";
import { ArrowUpDown, Edit, Eye, Trash2 } from "lucide-react";

export const getClientColumns = (handleDelete, handleEdit, handleToggleStatus) => [
    {
        id: "select",
        header: ({ table }) => (
            <Checkbox
                checked={table.getIsAllPageRowsSelected()}
                onCheckedChange={(value) =>
                    table.toggleAllPageRowsSelected(!!value)
                }
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
        accessorKey: "name",
        header: ({ column }) => {
            return (
                <Button
                    variant="ghost"
                    onClick={() =>
                        column.toggleSorting(column.getIsSorted() === "asc")
                    }
                >
                    Nombre
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
        cell: ({ row }) => (
            <div className="font-medium">{row.getValue("name")}</div>
        ),
    },
    {
        accessorKey: "company",
        header: "Empresa",
        cell: ({ row }) => <div>{row.getValue("company") || "-"}</div>,
    },
    {
        accessorKey: "email",
        header: "Email",
        cell: ({ row }) => <div>{row.getValue("email") || "-"}</div>,
    },
    {
        accessorKey: "phone",
        header: "Teléfono",
        cell: ({ row }) => <div>{row.getValue("phone") || "-"}</div>,
    },
    {
        accessorKey: "status",
        header: "Estado",
        cell: ({ row }) => {
            const client = row.original;
            const isActive = client.status === "active";

            return (
                <StatusSwitch
                    checked={isActive}
                    onChange={() => handleToggleStatus(client)}
                    activeText="Activo"
                    inactiveText="Inactivo"
                />
            );
        },
    },
    {
        id: "actions",
        enableHiding: false,
        cell: ({ row }) => {
            const client = row.original;
            return (
                <div className="flex justify-end gap-2">
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() =>
                            router.visit(route("clients.show", client.id))
                        }
                    >
                        <Eye className="h-4 w-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => handleEdit(client)}
                    >
                        <Edit className="h-4 w-4 text-blue-500" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => handleDelete(client)}
                    >
                        <Trash2 className="h-4 w-4 text-red-500" />
                    </Button>
                </div>
            );
        },
    },
];
