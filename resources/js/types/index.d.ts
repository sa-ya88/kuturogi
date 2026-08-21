export interface User {
    id: number;
    name: string;
    email: string;
    name_kana?: string;
    zip_code?: string;
    address?: string;
    email_verified_at?: string;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User | null;
    };
    demo?: {
        enabled: boolean;
        refreshHours?: number;
        allowRegistration?: boolean;
        guestEmail?: string;
        guestPassword?: string;
    };
    flash?: {
        success?: string | null;
    };
    errors: Record<string, string>;
};
