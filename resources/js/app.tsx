import React, { lazy, Suspense, useEffect, useState, type ComponentType, type FormEvent } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Navigate, Route, Routes, Link, useLocation, useNavigate } from 'react-router-dom';
import axios, { AxiosError } from 'axios';
import { Bell, CalendarDays, CheckSquare2, CircleUserRound, LayoutDashboard, Moon, Plus, Settings, Sun } from 'lucide-react';
import type { CalendarEvent } from './components/CalendarPanel';

const CalendarPanel = lazy(async () => {
    const module = await import('./components/CalendarPanel');
    return { default: module.CalendarPanel };
});
const ProductivityPanel = lazy(async () => {
    const module = await import('./components/ProductivityPanel');
    return { default: module.ProductivityPanel };
});

axios.defaults.withCredentials = true;
axios.defaults.headers.common['Accept'] = 'application/json';

type User = { id: number; name: string; email: string; timezone: string; language: string; theme: string };
type AuthMode = 'login' | 'register' | 'forgot' | 'reset';
type AuthForm = { name: string; email: string; password: string; password_confirmation: string; token: string };
type Task = {
    id: number;
    title: string;
    description?: string;
    status: 'todo' | 'in_progress' | 'completed';
    priority: 'low' | 'medium' | 'high' | 'urgent';
    start_at?: string;
    deadline_at?: string;
    category?: { id: number; name: string } | null;
};
type Reminder = {
    id: number;
    task_id: number;
    remind_at: string;
    channels: string[];
    status: string;
    is_enabled: boolean;
    snoozed_until?: string;
    task?: { title: string };
};
type DashboardStats = {
    total: number;
    completed: number;
    pending: number;
    overdue: number;
    today_tasks: Array<{ id: number; title: string }>;
};
type NavItem = { label: string; path: string; icon: ComponentType<{ size?: number }> };

const navItems: NavItem[] = [
    { label: 'Dashboard', path: '/', icon: LayoutDashboard },
    { label: 'Tugas', path: '/tasks', icon: CheckSquare2 },
    { label: 'Kalender', path: '/calendar', icon: CalendarDays },
    { label: 'Pengingat', path: '/reminders', icon: Bell },
    { label: 'Pengaturan', path: '/settings', icon: Settings },
];

function useAuth(): { user: User | null | undefined; setUser: React.Dispatch<React.SetStateAction<User | null | undefined>> } {
    const [user, setUser] = useState<User | null | undefined>(() => {
        try {
            const cached = sessionStorage.getItem('remindly-auth-user');
            return cached ? JSON.parse(cached) as User : undefined;
        } catch {
            return undefined;
        }
    });
    useEffect(() => {
        axios.get<{ data: User }>('/api/v1/user')
            .then(({ data }) => {
                sessionStorage.setItem('remindly-auth-user', JSON.stringify(data.data));
                setUser(data.data);
            })
            .catch(() => {
                sessionStorage.removeItem('remindly-auth-user');
                setUser(null);
            });
    }, []);
    return { user, setUser };
}

function apiError(error: unknown, fallback: string): string {
    const axiosError = error as AxiosError<{ message?: string; errors?: Record<string, string[]> }>;
    const response = axiosError.response?.data;
    return response?.message || Object.values(response?.errors ?? {}).flat()[0] || fallback;
}

function AuthPage({ mode }: { mode: AuthMode }) {
    const params = new URLSearchParams(window.location.search);
    const [form, setForm] = useState<AuthForm>({
        name: '', email: params.get('email') || '', password: '', password_confirmation: '',
        token: window.location.pathname.split('/').pop() || '',
    });
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);
    const submit = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        setLoading(true);
        setError('');
        try {
            await axios.get('/sanctum/csrf-cookie');
            const path = mode === 'register' ? '/api/v1/register' : mode === 'reset' ? '/api/v1/reset-password' : '/api/v1/login';
            const response = await axios.post<{ data: User }>(path, form);
            if (mode === 'login' || mode === 'register') {
                sessionStorage.setItem('remindly-auth-user', JSON.stringify(response.data.data));
                window.location.assign('/');
            } else {
                window.location.assign('/login');
            }
        } catch (caught) {
            setError(apiError(caught, 'Terjadi kesalahan.'));
        } finally {
            setLoading(false);
        }
    };
    const forgot = mode === 'forgot';
    const reset = mode === 'reset';
    return (
        <main className="auth-layout">
            <section className="auth-card card">
                <div className="brand auth-brand"><span className="brand-mark"><CheckSquare2 size={21} /></span>Remindly</div>
                <h1>{mode === 'register' ? 'Buat akun baru' : forgot ? 'Pulihkan akun' : reset ? 'Atur ulang password' : 'Masuk ke Remindly'}</h1>
                {error && <div className="form-error" role="alert">{error}</div>}
                <form onSubmit={submit} className="auth-form">
                    {mode === 'register' && <label>Nama<input value={form.name} onChange={(event) => setForm({ ...form, name: event.target.value })} required /></label>}
                    <label>Email<input type="email" value={form.email} onChange={(event) => setForm({ ...form, email: event.target.value })} required /></label>
                    {!forgot && <label>Password<input type="password" value={form.password} onChange={(event) => setForm({ ...form, password: event.target.value })} minLength={8} required /></label>}
                    {(mode === 'register' || reset) && <label>Konfirmasi Password<input type="password" value={form.password_confirmation} onChange={(event) => setForm({ ...form, password_confirmation: event.target.value })} minLength={8} required /></label>}
                    {forgot ? (
                        <button className="primary-button" type="button" onClick={async () => {
                            try {
                                await axios.get('/sanctum/csrf-cookie');
                                await axios.post('/api/v1/forgot-password', { email: form.email });
                                setError('Jika email terdaftar, tautan reset telah dikirim.');
                            } catch (caught) { setError(apiError(caught, 'Permintaan gagal.')); }
                        }}>Kirim tautan reset</button>
                    ) : <button className="primary-button" type="submit" disabled={loading}>{loading ? 'Memproses...' : mode === 'register' ? 'Buat akun' : 'Masuk'}</button>}
                </form>
                <div className="auth-links">
                    {mode === 'login' && <><Link to="/forgot-password">Lupa password?</Link><span>Belum punya akun? <Link to="/register">Daftar</Link></span></>}
                    {mode !== 'login' && <Link to="/login">Kembali ke login</Link>}
                </div>
            </section>
        </main>
    );
}

function SettingsPage({ setUser }: { setUser: React.Dispatch<React.SetStateAction<User | null | undefined>> }) {
    const navigate = useNavigate();
    const [timezone, setTimezone] = useState('Asia/Jakarta');
    const [language, setLanguage] = useState<'id' | 'en'>('id');
    const [theme, setTheme] = useState<'light' | 'dark' | 'system'>('system');
    const [error, setError] = useState('');
    const [saved, setSaved] = useState(false);

    useEffect(() => {
        axios.get<{ data: { timezone: string; language: 'id' | 'en'; theme: 'light' | 'dark' | 'system' } }>('/api/v1/settings')
            .then(({ data }) => {
                setTimezone(data.data.timezone);
                setLanguage(data.data.language);
                setTheme(data.data.theme);
            }).catch((caught) => setError(apiError(caught, 'Gagal memuat pengaturan.')));
    }, []);

    const save = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        setSaved(false);
        try {
            await axios.put('/api/v1/settings', { timezone, language, theme });
            setSaved(true);
            localStorage.setItem('remindly-theme', theme === 'system' ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') : theme);
            document.documentElement.classList.toggle('dark', theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches));
        } catch (caught) { setError(apiError(caught, 'Gagal menyimpan pengaturan.')); }
    };

    const exportData = async () => {
        const response = await axios.get<Blob>('/api/v1/settings/export', { responseType: 'blob' });
        const url = window.URL.createObjectURL(response.data);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'remindly-export.json';
        link.click();
        window.URL.revokeObjectURL(url);
    };

    const deleteAccount = async () => {
        const password = window.prompt('Masukkan password untuk menghapus akun:');
        if (!password) return;
        if (!window.confirm('Akun dan seluruh data akan dihapus permanen. Lanjutkan?')) return;
        try {
            await axios.delete('/api/v1/settings/account', { data: { password } });
            sessionStorage.removeItem('remindly-auth-user');
            setUser(null);
            navigate('/login');
        } catch (caught) { setError(apiError(caught, 'Gagal menghapus akun.')); }
    };

    return (
        <section className="card panel page-panel">
            <div className="panel-header"><h2>Pengaturan</h2></div>
            {error && <div className="form-error" role="alert">{error}</div>}
            {saved && <div className="success-line" role="status">Pengaturan berhasil disimpan.</div>}
            <form className="settings-form" onSubmit={save}>
                <label>Timezone
                    <select value={timezone} onChange={(event) => setTimezone(event.target.value)}>
                        <option value="Asia/Jakarta">Asia/Jakarta</option>
                        <option value="Asia/Makassar">Asia/Makassar</option>
                        <option value="Asia/Jayapura">Asia/Jayapura</option>
                        <option value="UTC">UTC</option>
                    </select>
                </label>
                <label>Bahasa
                    <select value={language} onChange={(event) => setLanguage(event.target.value as 'id' | 'en')}>
                        <option value="id">Indonesia</option>
                        <option value="en">English</option>
                    </select>
                </label>
                <label>Tema
                    <select value={theme} onChange={(event) => setTheme(event.target.value as 'light' | 'dark' | 'system')}>
                        <option value="system">Ikuti sistem</option>
                        <option value="light">Terang</option>
                        <option value="dark">Gelap</option>
                    </select>
                </label>
                <button className="primary-button" type="submit">Simpan pengaturan</button>
            </form>
            <div className="settings-actions">
                <button className="secondary-button" onClick={() => void exportData()}>Ekspor data JSON</button>
                <button className="danger-button" onClick={() => void deleteAccount()}>Hapus akun</button>
            </div>
        </section>
    );
}

function ProtectedApp({ user, setUser }: { user: User; setUser: React.Dispatch<React.SetStateAction<User | null | undefined>> }) {
    return (
        <Routes>
            <Route path="/" element={<AppShell user={user} setUser={setUser}><DashboardPage /></AppShell>} />
            <Route path="/tasks" element={<AppShell user={user} setUser={setUser}><TasksPage /></AppShell>} />
            <Route path="/calendar" element={<AppShell user={user} setUser={setUser}><CalendarPage /></AppShell>} />
            <Route path="/reminders" element={<AppShell user={user} setUser={setUser}><RemindersPage /></AppShell>} />
            <Route path="/settings" element={<AppShell user={user} setUser={setUser}><SettingsPage setUser={setUser} /></AppShell>} />
            <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
    );
}

function App() {
    const { user, setUser } = useAuth();
    useEffect(() => {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').catch(() => {});
        }
    }, []);
    if (user === undefined) return <main className="auth-layout"><div className="card stat-card">Memuat...</div></main>;
    return (
        <Routes>
            <Route path="/login" element={user ? <Navigate to="/" replace /> : <AuthPage mode="login" />} />
            <Route path="/register" element={user ? <Navigate to="/" replace /> : <AuthPage mode="register" />} />
            <Route path="/forgot-password" element={user ? <Navigate to="/" replace /> : <AuthPage mode="forgot" />} />
            <Route path="/reset-password/:token" element={user ? <Navigate to="/" replace /> : <AuthPage mode="reset" />} />
            <Route path="*" element={user ? <ProtectedApp user={user} setUser={setUser} /> : <Navigate to="/login" replace />} />
        </Routes>
    );
}

const root = document.getElementById('app');
if (root) {
    createRoot(root).render(<React.StrictMode><BrowserRouter><App /></BrowserRouter></React.StrictMode>);
}

function AppShell({ user, setUser, children }: { user: User; setUser: React.Dispatch<React.SetStateAction<User | null | undefined>>; children: React.ReactNode }) {
    const navigate = useNavigate();
    const location = useLocation();
    const [dark, setDark] = useState(() => localStorage.getItem('remindly-theme') === 'dark');
    const [unread, setUnread] = useState(0);
    useEffect(() => {
        document.documentElement.classList.toggle('dark', dark);
        localStorage.setItem('remindly-theme', dark ? 'dark' : 'light');
    }, [dark]);
    useEffect(() => {
        axios.get<{ data: { unread: number } }>('/api/v1/notifications/unread-count')
            .then(({ data }) => setUnread(data.data.unread)).catch(() => setUnread(0));
    }, [location.pathname]);
    const logout = async () => {
        await axios.post('/api/v1/logout');
        sessionStorage.removeItem('remindly-auth-user');
        setUser(null);
        navigate('/login');
    };
    const activePath = (path: string) => path === '/' ? location.pathname === '/' : location.pathname.startsWith(path);
    return (
        <div className="app-shell">
            <aside className="sidebar">
                <button className="brand brand-button" onClick={() => navigate('/')}>
                    <span className="brand-mark"><CheckSquare2 size={21} /></span>Remindly
                </button>
                <nav className="nav" aria-label="Navigasi utama">
                    {navItems.map(({ label, path, icon: Icon }) => (
                        <button className={`nav-item ${activePath(path) ? 'active' : ''}`} key={path} onClick={() => navigate(path)} aria-current={activePath(path) ? 'page' : undefined}>
                            <Icon size={18} /> {label}
                        </button>
                    ))}
                </nav>
                <div className="sidebar-footer"><button className="nav-item" onClick={logout}><CircleUserRound size={18} /> Keluar</button></div>
            </aside>
            <main className="main">
                <header className="topbar">
                    <div className="greeting"><h1>Halo, {user.name}</h1><p>Kelola hidup Anda dengan lebih terorganisir.</p></div>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                        <button className="icon-button" onClick={() => setDark((value) => !value)} aria-label="Ubah tema">{dark ? <Sun size={18} /> : <Moon size={18} />}</button>
                        <button className="icon-button notification-button" onClick={() => navigate('/reminders')} aria-label="Notifikasi"><Bell size={18} />{unread > 0 && <span className="notification-badge">{unread}</span>}</button>
                    </div>
                </header>
                {children}
            </main>
            <nav className="mobile-nav" aria-label="Navigasi mobile">
                {navItems.map(({ label, path, icon: Icon }) => (
                    <button className={activePath(path) ? 'active' : ''} key={path} onClick={() => navigate(path)} aria-label={label} aria-current={activePath(path) ? 'page' : undefined}>
                        <Icon size={20} />
                    </button>
                ))}
            </nav>
        </div>
    );
}

function DashboardPage() {
    const [stats, setStats] = useState<DashboardStats>({ total: 0, completed: 0, pending: 0, overdue: 0, today_tasks: [] });
    const [health, setHealth] = useState<'ok' | 'offline' | null>(null);
    const [calendarEvents, setCalendarEvents] = useState<CalendarEvent[]>([]);
    const [productivity, setProductivity] = useState<{ labels: string[]; completed: number[] }>({ labels: [], completed: [] });
    const [showQuickAdd, setShowQuickAdd] = useState(false);
    const [taskTitle, setTaskTitle] = useState('');

    useEffect(() => {
        axios.get<{ data: DashboardStats }>('/api/v1/dashboard').then(({ data }) => setStats(data.data)).catch(() => {});
        axios.get<{ data: { status: string } }>('/api/v1/health').then(({ data }) => setHealth(data.data.status === 'ok' ? 'ok' : 'offline')).catch(() => setHealth('offline'));
        axios.get<{ data: CalendarEvent[] }>('/api/v1/calendar/events', {
            params: { start: new Date().toISOString(), end: new Date(Date.now() + 31 * 864e5).toISOString() },
        }).then(({ data }) => setCalendarEvents(data.data)).catch(() => {});
        axios.get<{ data: { labels: string[]; completed: number[] } }>('/api/v1/analytics/productivity', { params: { days: 30 } })
            .then(({ data }) => setProductivity(data.data)).catch(() => {});
    }, []);

    return (
        <>
            {health === 'ok' && <div className="health-line"><span className="health-dot" /> API aktif</div>}
            <section className="dashboard-grid" aria-label="Ringkasan">
                {[['Total tugas', stats.total], ['Selesai', stats.completed], ['Menunggu', stats.pending], ['Terlewat', stats.overdue]].map(([label, value]) => (
                    <article className="card stat-card" key={String(label)}><div className="label">{String(label)}</div><div className="value">{Number(value)}</div></article>
                ))}
            </section>
            <section className="content-grid">
                <article className="card panel">
                    <div className="panel-header"><h2>Tugas hari ini</h2><button className="icon-button" onClick={() => setShowQuickAdd(true)} aria-label="Tambah tugas"><Plus size={18} /></button></div>
                    {stats.today_tasks.length ? (
                        <div className="task-list">{stats.today_tasks.map((task) => <div className="task-row" key={task.id}><CheckSquare2 size={18} /><span>{task.title}</span></div>)}</div>
                    ) : <div className="empty-state"><div><CheckSquare2 size={34} /><p>Belum ada tugas hari ini.</p></div></div>}
                </article>
                <article className="card panel"><div className="panel-header"><h2>Produktivitas</h2></div><Suspense fallback={<div>Memuat chart...</div>}><ProductivityPanel labels={productivity.labels} completed={productivity.completed} /></Suspense></article>
            </section>
            {showQuickAdd && (
                <div className="modal-backdrop" role="presentation" onMouseDown={(event) => event.target === event.currentTarget && setShowQuickAdd(false)}>
                    <section className="card modal" role="dialog" aria-modal="true" aria-label="Tambah tugas">
                        <div className="panel-header"><h2>Tambah tugas cepat</h2></div>
                        <form className="auth-form" onSubmit={(event) => {
                            event.preventDefault();
                            const now = new Date();
                            const optimisticTitle = taskTitle;
                            const optimisticId = -Date.now();
                            setStats((current) => ({
                                ...current,
                                total: current.total + 1,
                                pending: current.pending + 1,
                                today_tasks: [{ id: optimisticId, title: optimisticTitle }, ...current.today_tasks],
                            }));
                            setTaskTitle('');
                            setShowQuickAdd(false);
                            void axios.post<{ data: { id: number; title: string } }>('/api/v1/tasks', {
                                title: optimisticTitle,
                                start_at: now.toISOString(),
                                deadline_at: new Date(now.getTime() + 3600000).toISOString(),
                                priority: 'medium',
                                status: 'todo',
                            }).then(({ data }) => {
                                setStats((current) => ({
                                    ...current,
                                    today_tasks: current.today_tasks.map((task) =>
                                        task.id === optimisticId ? { id: data.data.id, title: data.data.title } : task,
                                    ),
                                }));
                            }).catch(() => {
                                setStats((current) => ({
                                    ...current,
                                    total: Math.max(0, current.total - 1),
                                    pending: Math.max(0, current.pending - 1),
                                    today_tasks: current.today_tasks.filter((task) => task.id !== optimisticId),
                                }));
                            });
                        }}>
                            <label>Judul tugas<input value={taskTitle} onChange={(event) => setTaskTitle(event.target.value)} required autoFocus /></label>
                            <button className="primary-button" type="submit">Simpan tugas</button>
                        </form>
                    </section>
                </div>
            )}
        </>
    );
}

function TasksPage() {
    const [tasks, setTasks] = useState<Task[]>([]);
    const [title, setTitle] = useState('');
    const [priority, setPriority] = useState<Task['priority']>('medium');
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    const load = async () => {
        setLoading(true);
        try {
            const response = await axios.get<{ data: Task[] }>('/api/v1/tasks');
            setTasks(response.data.data);
        } catch (caught) {
            setError(apiError(caught, 'Gagal memuat tugas.'));
        } finally {
            setLoading(false);
        }
    };
    useEffect(() => { void load(); }, []);

    const createTask = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        try {
            await axios.post('/api/v1/tasks', { title, priority, status: 'todo' });
            setTitle('');
            await load();
        } catch (caught) { setError(apiError(caught, 'Gagal membuat tugas.')); }
    };
    const completeTask = async (task: Task) => {
        await axios.patch(`/api/v1/tasks/${task.id}/${task.status === 'completed' ? 'reopen' : 'complete'}`);
        await load();
    };
    const deleteTask = async (task: Task) => {
        if (!window.confirm(`Hapus tugas "${task.title}"?`)) return;
        await axios.delete(`/api/v1/tasks/${task.id}`);
        await load();
    };
    const editTask = async (task: Task) => {
        const nextTitle = window.prompt('Judul tugas', task.title);
        if (!nextTitle || nextTitle === task.title) return;
        await axios.put(`/api/v1/tasks/${task.id}`, { title: nextTitle });
        await load();
    };

    return (
        <section className="card panel page-panel">
            <div className="panel-header"><h2>Daftar Tugas</h2></div>
            {error && <div className="form-error" role="alert">{error}</div>}
            <form className="task-create" onSubmit={createTask}>
                <label>Judul tugas<input value={title} onChange={(event) => setTitle(event.target.value)} required /></label>
                <label>Prioritas
                    <select value={priority} onChange={(event) => setPriority(event.target.value as Task['priority'])}>
                        <option value="low">Rendah</option><option value="medium">Sedang</option><option value="high">Tinggi</option><option value="urgent">Urgent</option>
                    </select>
                </label>
                <button className="primary-button" type="submit">Tambah</button>
            </form>
            {loading ? <div className="empty-state"><p>Memuat tugas...</p></div> : tasks.length === 0 ? (
                <div className="empty-state"><div><CheckSquare2 size={34} /><p>Belum ada tugas. Tambahkan tugas pertama Anda.</p></div></div>
            ) : (
                <div className="task-table">
                    {tasks.map((task) => (
                        <article className="task-row task-row-expanded" key={task.id}>
                            <div className="task-main"><strong>{task.title}</strong><span className={`priority priority-${task.priority}`}>{task.priority}</span></div>
                            <div className="task-actions">
                                <button className="secondary-button" onClick={() => void editTask(task)}>Edit</button>
                                <button className="secondary-button" onClick={() => void completeTask(task)}>{task.status === 'completed' ? 'Buka lagi' : 'Selesai'}</button>
                                <button className="danger-button" onClick={() => void deleteTask(task)}>Hapus</button>
                            </div>
                        </article>
                    ))}
                </div>
            )}
        </section>
    );
}

function CalendarPage() {
    const [events, setEvents] = useState<CalendarEvent[]>([]);
    const [error, setError] = useState('');
    useEffect(() => {
        const start = new Date();
        const end = new Date();
        end.setMonth(end.getMonth() + 3);
        axios.get<{ data: CalendarEvent[] }>('/api/v1/calendar/events', {
            params: { start: start.toISOString(), end: end.toISOString() },
        }).then(({ data }) => setEvents(data.data)).catch((caught) => setError(apiError(caught, 'Gagal memuat kalender.')));
    }, []);
    return (
        <section className="card panel page-panel calendar-page">
            <div className="panel-header"><h2>Kalender</h2></div>
            {error && <div className="form-error" role="alert">{error}</div>}
            <Suspense fallback={<div>Memuat kalender...</div>}><CalendarPanel events={events} /></Suspense>
        </section>
    );
}

function RemindersPage() {
    const [reminders, setReminders] = useState<Reminder[]>([]);
    const [tasks, setTasks] = useState<Task[]>([]);
    const [taskId, setTaskId] = useState('');
    const [remindAt, setRemindAt] = useState('');
    const [error, setError] = useState('');

    const load = async () => {
        try {
            const [reminderResponse, taskResponse] = await Promise.all([
                axios.get<{ data: Reminder[] }>('/api/v1/reminders'),
                axios.get<{ data: Task[] }>('/api/v1/tasks'),
            ]);
            setReminders(reminderResponse.data.data);
            setTasks(taskResponse.data.data);
        } catch (caught) { setError(apiError(caught, 'Gagal memuat pengingat.')); }
    };
    useEffect(() => { void load(); }, []);

    return (
        <section className="card panel page-panel">
            <div className="panel-header"><h2>Pengingat</h2></div>
            {error && <div className="form-error" role="alert">{error}</div>}
            <form className="task-create" onSubmit={async (event) => {
                event.preventDefault();
                try {
                    await axios.post('/api/v1/reminders', {
                        task_id: Number(taskId),
                        remind_at: new Date(remindAt).toISOString(),
                        channels: ['in_app'],
                    });
                    setTaskId('');
                    setRemindAt('');
                    await load();
                } catch (caught) { setError(apiError(caught, 'Gagal membuat pengingat.')); }
            }}>
                <label>Tugas
                    <select value={taskId} onChange={(event) => setTaskId(event.target.value)} required>
                        <option value="">Pilih tugas</option>
                        {tasks.map((task) => <option value={task.id} key={task.id}>{task.title}</option>)}
                    </select>
                </label>
                <label>Waktu<input type="datetime-local" value={remindAt} onChange={(event) => setRemindAt(event.target.value)} required /></label>
                <button className="primary-button" type="submit">Tambah</button>
            </form>
            {reminders.length === 0 ? <div className="empty-state"><div><Bell size={34} /><p>Belum ada pengingat.</p></div></div> : (
                <div className="task-table">
                    {reminders.map((reminder) => (
                        <article className="task-row task-row-expanded" key={reminder.id}>
                            <div className="task-main"><strong>{reminder.task?.title || `Task #${reminder.task_id}`}</strong><span>{new Date(reminder.remind_at).toLocaleString('id-ID')}</span></div>
                            <div className="task-actions">
                                <button className="secondary-button" onClick={async () => { await axios.patch(`/api/v1/reminders/${reminder.id}/toggle`); await load(); }}>{reminder.is_enabled ? 'Nonaktifkan' : 'Aktifkan'}</button>
                                <button className="secondary-button" onClick={async () => { await axios.post(`/api/v1/reminders/${reminder.id}/snooze`, { minutes: 10 }); await load(); }}>Snooze 10m</button>
                                <button className="danger-button" onClick={async () => { await axios.delete(`/api/v1/reminders/${reminder.id}`); await load(); }}>Hapus</button>
                            </div>
                        </article>
                    ))}
                </div>
            )}
        </section>
    );
}
