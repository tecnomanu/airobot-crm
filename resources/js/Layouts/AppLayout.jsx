import NotificationPermissionBanner from "@/Components/Common/NotificationPermissionBanner";
import PageHeader from "@/Components/Common/PageHeader";
import { Avatar, AvatarFallback } from "@/Components/ui/avatar";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarProvider,
    SidebarTrigger,
    useSidebar,
} from "@/Components/ui/sidebar";
import { Toaster } from "@/Components/ui/sonner";
import { cn } from "@/lib/utils";
import { Link, usePage } from "@inertiajs/react";
import {
    Bot,
    Building2,
    Home,
    Link2,
    LogOut,
    Megaphone,
    MessageSquare,
    Phone,
    Settings,
    Table,
    Users,
} from "lucide-react";

// Navigation items - filtered by user permissions
const getNavigation = (user, permissions) => {
    const items = [];

    // Always visible
    items.push({ name: "Dashboard", href: route("dashboard"), icon: Home });
    items.push({ name: "Leads Manager", href: route("leads.index"), icon: Users });
    items.push({ name: "Messages", href: route("messages.index"), icon: MessageSquare });
    items.push({ name: "Campaigns", href: route("campaigns.index"), icon: Megaphone });
    items.push({ name: "Sources", href: route("sources.index"), icon: Link2 });

    // Clients - only for global users (admin/supervisor)
    if (permissions?.can_view_clients) {
        items.push({ name: "Clients", href: route("clients.index"), icon: Building2 });
    }

    // Users - admin/supervisor only
    if (permissions?.can_view_users) {
        items.push({
            name: "Users",
            href: route("users.index"),
            icon: Users,
            iconColor: "text-purple-400",
        });
    }

    // Retell Agents - only for global users (admin/supervisor)
    if (permissions?.can_view_retell_agents) {
        items.push({ name: "Retell Agents", href: route("call-agents.index"), icon: Bot });
    }

    // Call History - visible to all
    if (permissions?.can_view_call_history) {
        items.push({ name: "Call History", href: route("lead-calls.index"), icon: Phone });
    }

    // Calculator - global users or admin
    if (permissions?.can_view_calculator) {
        items.push({ name: "Calculator", href: route("calculator.index"), icon: Table });
    }

    // Integrations - global users or admin
    if (permissions?.can_view_integrations) {
        items.push({
            name: "Integrations",
            href: route("settings.integrations"),
            icon: Settings,
        });
    }

    return items;
};

function AppSidebar() {
    const { auth } = usePage().props;
    const user = auth.user;
    const permissions = auth.permissions;
    const { open } = useSidebar();
    const navigation = getNavigation(user, permissions);

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
        <Sidebar collapsible="icon" className="border-r-0">
            {/* Header aligned with content header */}
            <SidebarHeader className="h-12 flex items-center justify-center border-b border-gray-100 bg-white">
                <div className="flex items-center gap-2 px-2">
                    <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white">
                        <span className="text-sm font-bold">A</span>
                    </div>
                    {open && (
                        <span className="text-base font-semibold text-gray-900">
                            AiRobot
                        </span>
                    )}
                </div>
            </SidebarHeader>
            <SidebarContent className="bg-white px-2 pt-4">
                <SidebarGroup>
                    <SidebarGroupContent>
                        <SidebarMenu className="space-y-1">
                            {navigation.map((item) => {
                                const isActive = isActiveRoute(item.href);
                                return (
                                    <SidebarMenuItem
                                        key={item.name}
                                        className="relative"
                                    >
                                        {/* Active indicator bar */}
                                        {isActive && (
                                            <div className="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-6 bg-indigo-600 rounded-r-full" />
                                        )}
                                        <SidebarMenuButton
                                            asChild
                                            isActive={isActive}
                                            className={cn(
                                                "h-9 rounded-lg transition-all ml-1",
                                                isActive
                                                    ? "bg-indigo-50 text-indigo-700 font-medium"
                                                    : "text-gray-600 hover:bg-gray-50 hover:text-gray-900"
                                            )}
                                        >
                                            <Link href={item.href}>
                                                <item.icon
                                                    className={cn(
                                                        "h-4 w-4",
                                                        isActive
                                                            ? "text-indigo-600"
                                                            : "text-gray-400"
                                                    )}
                                                />
                                                <span className="text-sm">
                                                    {item.name}
                                                </span>
                                            </Link>
                                        </SidebarMenuButton>
                                    </SidebarMenuItem>
                                );
                            })}
                        </SidebarMenu>
                    </SidebarGroupContent>
                </SidebarGroup>
            </SidebarContent>
            <SidebarFooter className="border-t border-gray-100 p-2 bg-white">
                <DropdownMenu>
                    <DropdownMenuTrigger className="w-full">
                        <div className="flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-gray-50">
                            <Avatar className="h-7 w-7 shrink-0">
                                <AvatarFallback className="text-xs bg-indigo-100 text-indigo-700">
                                    {getUserInitials(user.name)}
                                </AvatarFallback>
                            </Avatar>
                            {open && (
                                <div className="flex flex-1 flex-col items-start overflow-hidden">
                                    <span className="text-xs font-medium truncate w-full text-gray-900">
                                        {user.name}
                                    </span>
                                    <span className="text-[10px] text-gray-500 truncate w-full">
                                        {user.email}
                                    </span>
                                </div>
                            )}
                        </div>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="w-56">
                        <DropdownMenuLabel className="text-xs">
                            Mi Cuenta
                        </DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem asChild className="text-sm">
                            <Link href={route("profile.edit")}>
                                <Settings className="mr-2 h-3.5 w-3.5" />
                                <span>Perfil</span>
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem asChild className="text-sm">
                            <Link
                                href={route("logout")}
                                method="post"
                                as="button"
                                className="w-full"
                            >
                                <LogOut className="mr-2 h-3.5 w-3.5" />
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
            <div className="flex min-h-screen w-full bg-gray-50">
                <AppSidebar />
                <div className="flex flex-1 flex-col">
                    {/* Header perfectly aligned with sidebar header */}
                    <header className="sticky top-0 z-10 flex h-12 items-center gap-3 border-b border-gray-100 bg-white px-4">
                        <SidebarTrigger className="h-7 w-7 text-gray-500 hover:text-gray-700" />
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
                            stretch ? "p-0" : "p-4"
                        )}
                    >
                        {children}
                    </main>
                </div>
            </div>
            <Toaster richColors position="top-right" />
            <NotificationPermissionBanner />
        </SidebarProvider>
    );
}
