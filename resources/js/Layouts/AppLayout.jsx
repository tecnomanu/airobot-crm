import PageHeader from "@/Components/common/PageHeader";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarProvider,
    SidebarTrigger,
    useSidebar,
} from "@/components/ui/sidebar";
import { Toaster } from "@/components/ui/sonner";
import { cn } from "@/lib/utils";
import { Link, usePage } from "@inertiajs/react";
import {
    Bot,
    Building2,
    Home,
    LogOut,
    Megaphone,
    MessageSquare,
    Phone,
    PlugZap,
    Settings,
    Users,
    Webhook,
    Table,
} from "lucide-react";

const navigation = [
    { name: "Dashboard", href: route("dashboard"), icon: Home },
    { name: "Leads", href: route("leads.index"), icon: Users },
    {
        name: "Leads Intención",
        href: route("leads-intencion.index"),
        icon: MessageSquare,
    },
    { name: "Campañas", href: route("campaigns.index"), icon: Megaphone },
    { name: "Fuentes", href: route("sources.index"), icon: PlugZap },
    { name: "Clientes", href: route("clients.index"), icon: Building2 },
    {
        name: "Agentes de Llamadas",
        href: route("call-agents.index"),
        icon: Bot,
    },
    {
        name: "Historial Llamadas",
        href: route("call-history.index"),
        icon: Phone,
    },
    {
        name: "Webhook Config",
        href: route("webhook-config.index"),
        icon: Webhook,
    },
    {
        name: "Excel",
        href: route("excel.index"),
        icon: Table,
    },
];

function AppSidebar() {
    const { auth } = usePage().props;
    const user = auth.user;
    const { open } = useSidebar();

    const getUserInitials = (name) => {
        return name
            .split(" ")
            .map((n) => n[0])
            .join("")
            .toUpperCase()
            .substring(0, 2);
    };

    const isActiveRoute = (href) => {
        const currentPath = window.location.pathname;
        let itemPath;
        try {
            const urlObj = new URL(href, window.location.origin);
            itemPath = urlObj.pathname;
        } catch {
            itemPath = href;
        }

        if (currentPath === itemPath) return true;

        if (
            itemPath !== "/" &&
            itemPath !== "/dashboard" &&
            currentPath.startsWith(itemPath)
        ) {
            return true;
        }

        return false;
    };

    return (
        <Sidebar collapsible="icon">
            <SidebarHeader className="border-b">
                <div className="flex h-14 items-center gap-2 px-3">
                    <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                        <Megaphone className="h-5 w-5" />
                    </div>
                    {open && (
                        <span className="text-lg font-semibold">AIRobot</span>
                    )}
                </div>
            </SidebarHeader>
            <SidebarContent>
                <SidebarGroup>
                    <SidebarGroupLabel>Navegación</SidebarGroupLabel>
                    <SidebarGroupContent>
                        <SidebarMenu>
                            {navigation.map((item) => (
                                <SidebarMenuItem key={item.name}>
                                    <SidebarMenuButton
                                        asChild
                                        isActive={isActiveRoute(item.href)}
                                    >
                                        <Link href={item.href}>
                                            <item.icon className="h-4 w-4" />
                                            <span>{item.name}</span>
                                        </Link>
                                    </SidebarMenuButton>
                                </SidebarMenuItem>
                            ))}
                        </SidebarMenu>
                    </SidebarGroupContent>
                </SidebarGroup>
            </SidebarContent>
            <SidebarFooter className="border-t p-3">
                <DropdownMenu>
                    <DropdownMenuTrigger className="w-full">
                        <div className="flex items-center gap-3 rounded-lg px-2 py-2 hover:bg-accent">
                            <Avatar className="h-8 w-8 shrink-0">
                                <AvatarFallback>
                                    {getUserInitials(user.name)}
                                </AvatarFallback>
                            </Avatar>
                            {open && (
                                <div className="flex flex-1 flex-col items-start text-sm overflow-hidden">
                                    <span className="font-medium truncate w-full">
                                        {user.name}
                                    </span>
                                    <span className="text-xs text-muted-foreground truncate w-full">
                                        {user.email}
                                    </span>
                                </div>
                            )}
                        </div>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="w-56">
                        <DropdownMenuLabel>Mi Cuenta</DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem asChild>
                            <Link href={route("profile.edit")}>
                                <Settings className="mr-2 h-4 w-4" />
                                <span>Configuración</span>
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem asChild>
                            <Link
                                href={route("logout")}
                                method="post"
                                as="button"
                                className="w-full"
                            >
                                <LogOut className="mr-2 h-4 w-4" />
                                <span>Cerrar Sesión</span>
                            </Link>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarFooter>
        </Sidebar>
    );
}

export default function AppLayout({ children, stretch = false, header }) {
    return (
        <SidebarProvider>
            <div className="flex min-h-screen w-full">
                <AppSidebar />
                <div className="flex flex-1 flex-col">
                    <header className="sticky top-0 z-10 flex h-14 items-center gap-4 border-b bg-background px-6">
                        <SidebarTrigger />
                        {header && (
                            <div className="flex-1 min-w-0">
                                <PageHeader {...header} compact />
                            </div>
                        )}
                        {!header && <div className="flex-1" />}
                    </header>
                    <main
                        className={cn(
                            "flex-1 overflow-y-auto",
                            stretch ? "p-0" : "p-6"
                        )}
                    >
                        {children}
                    </main>
                </div>
            </div>
            <Toaster richColors position="top-right" />
        </SidebarProvider>
    );
}
