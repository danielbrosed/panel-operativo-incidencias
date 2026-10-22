import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/field';
import { Textarea } from '@/components/ui/form-controls';

export function CommentForm({ url }: { url: string }) {
    const form = useForm({ body: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (form.processing) return;
        form.post(url, {
            errorBag: 'comment',
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <form noValidate onSubmit={submit} className="flex flex-col gap-3">
            <Field id="comment-body" label="Añadir comentario" error={form.errors.body}>
                {(control) => (
                    <Textarea
                        {...control}
                        name="body"
                        required
                        maxLength={2000}
                        placeholder="Qué se ha hecho, qué falta o con quién se ha hablado."
                        value={form.data.body}
                        onChange={(event) => form.setData('body', event.target.value)}
                    />
                )}
            </Field>
            <Button type="submit" aria-disabled={form.processing || undefined} className="self-end">
                Comentar
            </Button>
        </form>
    );
}
