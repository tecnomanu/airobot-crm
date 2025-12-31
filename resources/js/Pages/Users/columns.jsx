import StatusSwitch from "@/Components/Common/StatusSwitch";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Checkbox } from "@/Components/ui/checkbox";
import { ArrowUpDown, Briefcase, Edit, Trash2 } from "lucide-react";

const getRoleConfig = (role) => {
    const config = {
        admin: {
            label: "Administrador",
            className: "bg-purple-100 text-purple-800 hover:bg-purple-100",
        },
        supervisor: {
            label: "Supervisor",
            className: "bg-blue-100 text-blue-800 hover:bg-blue-100",
        },
        user: {
            label: "Usuario",
            className: "bg-gray-100 text-gray-800 hover:bg-gray-100",
        },
    };
    return config[role] || config.user;
};

export const getUserColumns = (
    handleDelete,
    handleEdit,
    handleToggleSeller,
    handleToggleStatus,
    canManage
) => [
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
        accessorKey: "email",
        header: "Email",
        cell: ({ row }) => (
            <div className="text-muted-foreground">{row.getValue("email")}</div>
        ),
    },
    {
        accessorKey: "role",
        header: "Rol",
        cell: ({ row }) => {
            const role = row.getValue("role");
            const config = getRoleConfig(role);
            return <Badge className={config.className}>{config.label}</Badge>;
        },
    },
    {
        accessorKey: "client",
        header: "Cliente",
        cell: ({ row }) => {
            const client = row.original.client;
            if (!client) {
                return (
                    <Badge variant="outline" className="text-xs">
                        Global
                    </Badge>
                );
            }
            return <span className="text-sm">{client.name}</span>;
        },
    },
    {
        accessorKey: "status",
        header: "Estado",
        cell: ({ row }) => {
            const user = row.original;
            const isActive = user.status === "active";

            if (!canManage) {
                return (
                    <span
                        className={`text-xs font-medium ${
                            isActive ? "text-green-600" : "text-gray-500"
                        }`}
                    >
                        {isActive ? "Activo" : "Inactivo"}
                    </span>
                );
            }

            return (
                <StatusSwitch
                    checked={isActive}
                    onChange={() => handleToggleStatus(user)}
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
            const user = row.original;

            if (!canManage) {
                return null;
            }

            return (
                <div className="flex justify-end gap-0.5">
                    <Button
                        variant="ghost"
                        size="icon"
                        className="h-7 w-7"
                        onClick={() => handleToggleSeller(user)}
                        title={
                            user.is_seller
                                ? "Quitar vendedor"
                                : "Hacer vendedor"
                        }
                    >
                        <Briefcase
                            className={`h-3.5 w-3.5 ${
                                user.is_seller
                                    ? "text-amber-500"
                                    : "text-gray-300"
                            }`}
                        />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        className="h-7 w-7"
                        onClick={() => handleEdit(user)}
                    >
                        <Edit className="h-3.5 w-3.5 text-blue-500" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        className="h-7 w-7"
                        onClick={() => handleDelete(user)}
                    >
                        <Trash2 className="h-3.5 w-3.5 text-red-500" />
                    </Button>
                </div>
            );
        },
    },
];
