import AppLayout from '@/Layouts/AppLayout';
import { Head } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';

export default function Edit({ mustVerifyEmail, status }) {
    return (
        <AppLayout
            header={{
                title: "Perfil",
                subtitle: "Administra tu información de perfil y contraseña",
            }}
        >
            <Head title="Perfil" />

            <div className="space-y-6">

                <Card>
                    <CardHeader>
                        <CardTitle>Información del Perfil</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <UpdateProfileInformationForm
                            mustVerifyEmail={mustVerifyEmail}
                            status={status}
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Actualizar Contraseña</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <UpdatePasswordForm />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Eliminar Cuenta</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <DeleteUserForm />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
