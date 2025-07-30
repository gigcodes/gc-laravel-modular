import { usePasskeys } from '@auth/hooks/use-passkeys';
import { Shield, Smartphone, Plus, MoreHorizontal, Edit, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';

import { Button } from '@shared/components/ui/button';
import { Input } from '@shared/components/ui/input';
import HeadingSmall from '@shared/components/heading-small';
import { Badge } from '@shared/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger
} from '@shared/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger
} from '@shared/components/ui/dropdown-menu';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle
} from '@shared/components/ui/alert-dialog';
import {
    Form,
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage
} from '@shared/components/ui/form';

const passkeyNameSchema = z.object({
    name: z
        .string()
        .min(1, 'Passkey name is required')
        .max(255, 'Name must be 255 characters or less')
});

type PasskeyNameForm = z.infer<typeof passkeyNameSchema>;

export const PasskeyForm = () => {
    const {
        passkeys,
        isLoading,
        error,
        canUse,
        registerNewPasskey,
        deletePasskey,
        updatePasskeyName,
        refreshPasskeys
    } = usePasskeys();

    const [isAddDialogOpen, setIsAddDialogOpen] = useState(false);
    const [editingPasskey, setEditingPasskey] = useState<string | null>(null);
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [passkeyToDelete, setPasskeyToDelete] = useState<string | null>(null);

    const addForm = useForm<PasskeyNameForm>({
        resolver: zodResolver(passkeyNameSchema),
        defaultValues: {
            name: ''
        }
    });

    const editForm = useForm<PasskeyNameForm>({
        resolver: zodResolver(passkeyNameSchema),
        defaultValues: {
            name: ''
        }
    });

    useEffect(() => {
        if (canUse) {
            refreshPasskeys();
        }
    }, [canUse, refreshPasskeys]);

    const handleAddPasskey = async (values: PasskeyNameForm) => {
        await registerNewPasskey(values.name || undefined);
        setIsAddDialogOpen(false);
        addForm.reset();
    };

    const handleEditPasskey = async (values: PasskeyNameForm) => {
        if (editingPasskey) {
            await updatePasskeyName(editingPasskey, values.name);
            setEditingPasskey(null);
            editForm.reset();
        }
    };

    const handleDeletePasskey = async () => {
        if (passkeyToDelete) {
            await deletePasskey(passkeyToDelete);
            setDeleteDialogOpen(false);
            setPasskeyToDelete(null);
        }
    };

    const startEditingPasskey = (passkey: { id: string; name: string }) => {
        setEditingPasskey(passkey.id);
        editForm.setValue('name', passkey.name);
    };

    const openDeleteDialog = (passkeyId: string) => {
        setPasskeyToDelete(passkeyId);
        setDeleteDialogOpen(true);
    };

    if (!canUse) {
        return (
            <div className="space-y-6">
                <HeadingSmall title="Passkeys" description="Manage your passkeys for secure, passwordless authentication" />
                <div className="p-4 border rounded-lg bg-muted/50">
                    <p className="text-sm text-muted-foreground">
                        Passkeys are not supported in this browser or environment. Please use a
                        modern browser with HTTPS to enable passkey functionality.
                    </p>
                </div>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <HeadingSmall title="Passkeys" description="Manage your passkeys for secure, passwordless authentication" />
            <div className="space-y-4">
                {error && (
                    <div className="p-3 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg">
                        {error}
                    </div>
                )}

                <div className="flex items-center justify-between">
                    <div className="text-sm text-muted-foreground">
                        {passkeys.length === 0
                            ? 'No passkeys configured'
                            : `${passkeys.length} passkey${passkeys.length === 1 ? '' : 's'} configured`}
                    </div>
                    <Dialog
                        open={isAddDialogOpen}
                        onOpenChange={setIsAddDialogOpen}
                    >
                        <DialogTrigger asChild>
                            <Button
                                size="sm"
                                disabled={isLoading}
                            >
                                <Plus className="h-4 w-4 mr-2" />
                                Add Passkey
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Add New Passkey</DialogTitle>
                                <DialogDescription>
                                    Give your passkey a name to help you identify it later. You'll
                                    be prompted to use your device's authentication method.
                                </DialogDescription>
                            </DialogHeader>
                            <Form {...addForm}>
                                <form
                                    onSubmit={addForm.handleSubmit(handleAddPasskey)}
                                    className="space-y-4"
                                >
                                    <FormField
                                        control={addForm.control}
                                        name="name"
                                        render={({ field }) => (
                                            <FormItem>
                                                <FormLabel>Passkey Name</FormLabel>
                                                <FormControl>
                                                    <Input
                                                        placeholder="e.g., My iPhone, Work Laptop"
                                                        {...field}
                                                    />
                                                </FormControl>
                                                <FormMessage />
                                            </FormItem>
                                        )}
                                    />
                                    <DialogFooter>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() => setIsAddDialogOpen(false)}
                                        >
                                            Cancel
                                        </Button>
                                        <Button
                                            type="submit"
                                            disabled={isLoading}
                                        >
                                            {isLoading ? 'Creating...' : 'Create Passkey'}
                                        </Button>
                                    </DialogFooter>
                                </form>
                            </Form>
                        </DialogContent>
                    </Dialog>
                </div>

                {passkeys.length > 0 && (
                    <div className="space-y-3">
                        {passkeys.map((passkey) => (
                            <div
                                key={passkey.id}
                                className="flex items-center justify-between p-4 border rounded-lg"
                            >
                                <div className="flex items-center gap-3">
                                    <div className="p-2 bg-primary/10 rounded-full">
                                        <Smartphone className="h-4 w-4 text-primary" />
                                    </div>
                                    <div>
                                        {editingPasskey === passkey.id ? (
                                            <Form {...editForm}>
                                                <form
                                                    onSubmit={editForm.handleSubmit(
                                                        handleEditPasskey
                                                    )}
                                                    className="flex items-center gap-2"
                                                >
                                                    <FormField
                                                        control={editForm.control}
                                                        name="name"
                                                        render={({ field }) => (
                                                            <FormItem>
                                                                <FormControl>
                                                                    <Input
                                                                        {...field}
                                                                        className="h-8 w-48"
                                                                        autoFocus
                                                                    />
                                                                </FormControl>
                                                            </FormItem>
                                                        )}
                                                    />
                                                    <Button
                                                        type="submit"
                                                        size="sm"
                                                        disabled={isLoading}
                                                    >
                                                        Save
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => setEditingPasskey(null)}
                                                    >
                                                        Cancel
                                                    </Button>
                                                </form>
                                            </Form>
                                        ) : (
                                            <>
                                                <div className="font-medium">{passkey.name}</div>
                                                <div className="text-sm text-muted-foreground">
                                                    Created {passkey.created_at}
                                                    {passkey.last_used_at && (
                                                        <> • Last used {passkey.last_used_at}</>
                                                    )}
                                                </div>
                                            </>
                                        )}
                                    </div>
                                </div>

                                {editingPasskey !== passkey.id && (
                                    <div className="flex items-center gap-2">
                                        <Badge variant="secondary">Active</Badge>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                >
                                                    <MoreHorizontal className="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem
                                                    onClick={() => startEditingPasskey(passkey)}
                                                >
                                                    <Edit className="h-4 w-4 mr-2" />
                                                    Rename
                                                </DropdownMenuItem>
                                                <DropdownMenuItem
                                                    onClick={() => openDeleteDialog(passkey.id)}
                                                    className="text-red-600"
                                                >
                                                    <Trash2 className="h-4 w-4 mr-2" />
                                                    Delete
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                )}

                {passkeys.length === 0 && (
                    <div className="text-center py-8 text-muted-foreground">
                        <Shield className="h-12 w-12 mx-auto mb-4 opacity-50" />
                        <p className="text-lg font-medium mb-2">No passkeys configured</p>
                        <p className="text-sm">
                            Add a passkey to enable secure, passwordless authentication for your
                            account.
                        </p>
                    </div>
                )}
            </div>
            <AlertDialog
                open={deleteDialogOpen}
                onOpenChange={setDeleteDialogOpen}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Are you absolutely sure?</AlertDialogTitle>
                        <AlertDialogDescription>
                            This action cannot be undone. This will permanently delete your passkey.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel onClick={() => setDeleteDialogOpen(false)}>
                            Cancel
                        </AlertDialogCancel>
                        <AlertDialogAction
                            onClick={handleDeletePasskey}
                            disabled={isLoading}
                        >
                            {isLoading ? 'Deleting...' : 'Delete'}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    );
};