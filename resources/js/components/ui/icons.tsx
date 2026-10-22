import type { ReactNode } from 'react';
import { cx } from '@/lib/cx';

export interface IconProps {
    className?: string;
}

// Decorative only: every icon sits next to a text that says the same thing.
function Svg({ className, children }: IconProps & { children: ReactNode }) {
    return (
        <svg
            viewBox="0 0 16 16"
            fill="none"
            stroke="currentColor"
            strokeWidth={1.75}
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
            focusable="false"
            className={cx('size-4 shrink-0', className)}
        >
            {children}
        </svg>
    );
}

export const IconCircle = (props: IconProps) => (
    <Svg {...props}>
        <circle cx="8" cy="8" r="5.5" />
    </Svg>
);

export const IconEye = (props: IconProps) => (
    <Svg {...props}>
        <path d="M1.75 8S4 3.75 8 3.75 14.25 8 14.25 8 12 12.25 8 12.25 1.75 8 1.75 8Z" />
        <circle cx="8" cy="8" r="1.75" />
    </Svg>
);

export const IconBan = (props: IconProps) => (
    <Svg {...props}>
        <circle cx="8" cy="8" r="5.5" />
        <path d="m4.1 4.1 7.8 7.8" />
    </Svg>
);

export const IconCheck = (props: IconProps) => (
    <Svg {...props}>
        <path d="m3.25 8.5 3 3 6.5-7" />
    </Svg>
);

export const IconClock = (props: IconProps) => (
    <Svg {...props}>
        <circle cx="8" cy="8" r="5.5" />
        <path d="M8 5v3l2 1.5" />
    </Svg>
);

export const IconFlag = (props: IconProps) => (
    <Svg {...props}>
        <path d="M3.5 14V2.5M3.5 3h8l-1.75 3 1.75 3h-8" />
    </Svg>
);

export const IconSearch = (props: IconProps) => (
    <Svg {...props}>
        <circle cx="7" cy="7" r="4.25" />
        <path d="m10.25 10.25 3.25 3.25" />
    </Svg>
);

export const IconClose = (props: IconProps) => (
    <Svg {...props}>
        <path d="m4 4 8 8M12 4l-8 8" />
    </Svg>
);

export const IconPlus = (props: IconProps) => (
    <Svg {...props}>
        <path d="M8 3v10M3 8h10" />
    </Svg>
);

export const IconArrowLeft = (props: IconProps) => (
    <Svg {...props}>
        <path d="M13 8H3m4-4L3 8l4 4" />
    </Svg>
);

export const IconChevronDown = (props: IconProps) => (
    <Svg {...props}>
        <path d="m4 6 4 4 4-4" />
    </Svg>
);
