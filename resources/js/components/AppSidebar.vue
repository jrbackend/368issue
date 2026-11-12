<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { LayoutGrid, Mail, MessageCircle, Newspaper, ScrollText, ShieldCheck } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { blog, dashboard } from '@/routes';
import { index as emailIndex } from '@/routes/email';
import { index as whatsappIndex } from '@/routes/whatsapp';
import { edit as traffic, logs as trafficLogs } from '@/routes/traffic';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Controle de tráfego',
        href: traffic(),
        icon: ShieldCheck,
    },
    {
        title: 'Logs de tráfego',
        href: trafficLogs(),
        icon: ScrollText,
    },
    {
        title: 'Disparo de E-mail',
        href: emailIndex(),
        icon: Mail,
    },
    {
        title: 'Disparo de WhatsApp',
        href: whatsappIndex(),
        icon: MessageCircle,
    },
    {
        title: 'Blog',
        href: blog(),
        icon: Newspaper,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
