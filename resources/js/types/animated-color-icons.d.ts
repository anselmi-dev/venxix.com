declare module '@animated-color-icons/lucide-vue' {
    import type { DefineComponent } from 'vue';

    type AnimatedIconProps = {
        size?: number;
        color?: string;
        primaryColor?: string;
        secondaryColor?: string;
        strokeWidth?: number;
        className?: string;
        label?: string;
    };

    type AnimatedIcon = DefineComponent<AnimatedIconProps>;

    export const AlertCircle: AnimatedIcon;
    export const Archive: AnimatedIcon;
    export const ArchiveRestore: AnimatedIcon;
    export const ArrowLeft: AnimatedIcon;
    export const ArrowRight: AnimatedIcon;
    export const Bell: AnimatedIcon;
    export const BookOpen: AnimatedIcon;
    export const Calendar: AnimatedIcon;
    export const Check: AnimatedIcon;
    export const ChevronLeft: AnimatedIcon;
    export const ChevronRight: AnimatedIcon;
    export const CircleCheck: AnimatedIcon;
    export const CircleDollarSign: AnimatedIcon;
    export const ChevronsUpDown: AnimatedIcon;
    export const Copy: AnimatedIcon;
    export const CreditCard: AnimatedIcon;
    export const Download: AnimatedIcon;
    export const Eye: AnimatedIcon;
    export const EyeOff: AnimatedIcon;
    export const Folder: AnimatedIcon;
    export const FolderGit2: AnimatedIcon;
    export const Info: AnimatedIcon;
    export const KeyRound: AnimatedIcon;
    export const Languages: AnimatedIcon;
    export const LayoutGrid: AnimatedIcon;
    export const LockKeyhole: AnimatedIcon;
    export const LogOut: AnimatedIcon;
    export const Menu: AnimatedIcon;
    export const Monitor: AnimatedIcon;
    export const Moon: AnimatedIcon;
    export const OctagonX: AnimatedIcon;
    export const Pencil: AnimatedIcon;
    export const Plus: AnimatedIcon;
    export const RefreshCw: AnimatedIcon;
    export const ScanLine: AnimatedIcon;
    export const Search: AnimatedIcon;
    export const Settings: AnimatedIcon;
    export const ShieldCheck: AnimatedIcon;
    export const Sparkles: AnimatedIcon;
    export const Sun: AnimatedIcon;
    export const Trash2: AnimatedIcon;
    export const TriangleAlert: AnimatedIcon;
    export const Upload: AnimatedIcon;
    export const User: AnimatedIcon;
    export const X: AnimatedIcon;
}
