import React, { lazy, Suspense, useEffect, useState, type ComponentType, type FormEvent } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Navigate, Route, Routes, Link, useNavigate } from 'react-router-dom';
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

type User = {
    id: number;
    name: string;
    email: string;
    timezone: string;
    language: string;
    theme: string;
};

type AuthMode = 'login' | 'register' | 'forgot' | 'reset';
type AuthForm = {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    token: string;
};
type DashboardStats = {
    total: number;
    completed: number;
    pending: number;
    overdue: number;
    today_tasks: Array<{ id: number; title: string }>;
};
type NavItem = { label: string; icon: ComponentType<{ size?: number }> };

const navItems: NavItem[] = [
    { label: 'Dashboard', icon: LayoutDashboard },
    { label: 'Tugas', icon: CheckSquare2 },
    { label: 'Kalender', icon: CalendarDays },
    { label: 'Pengingat', icon: Bell },
    { label: 'Pengaturan', icon: Settings },
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
            .catch((error: unknown) => {
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
        name: '',
        email: params.get('email') || '',
        password: '',
        password_confirmation: '',
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
            const path = mode === 'register'
                ? '/api/v1/register'
                : mode === 'reset'
                    ? '/api/v1/reset-password'
                    : '/api/v1/login';
            const authResponse = await axios.post<{ data: User }>(path, form);
            sessionStorage.setItem('remindly-auth-user', JSON.stringify(authResponse.data.data));
            if (mode === 'login' || mode === 'register') {
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
                            } catch (caught) {
                                setError(apiError(caught, 'Permintaan gagal.'));
                            }
                        }}>Kirim tautan reset</button>
                    ) : (
                        <button className="primary-button" type="submit" disabled={loading}>{loading ? 'Memproses...' : mode === 'register' ? 'Buat akun' : 'Masuk'}</button>
                    )}
                </form>
                <div className="auth-links">
                    {mode === 'login' && <><Link to="/forgot-password">Lupa password?</Link><span>Belum punya akun? <Link to="/register">Daftar</Link></span></>}
                    {mode !== 'login' && <Link to="/login">Kembali ke login</Link>}
                </div>
            </section>
        </main>
    );
}

function Dashboard({ user, setUser }: { user: User; setUser: React.Dispatch<React.SetStateAction<User | null | undefined>> }) {
    const navigate = useNavigate();
    const [dark, setDark] = useState(() => localStorage.getItem('remindly-theme') === 'dark');
    const [health, setHealth] = useState<'ok' | 'offline' | null>(null);
    const [stats, setStats] = useState<DashboardStats>({ total: 0, completed: 0, pending: 0, overdue: 0, today_tasks: [] });
    const [showQuickAdd, setShowQuickAdd] = useState(false);
    const [taskTitle, setTaskTitle] = useState('');
    const [unread, setUnread] = useState(0);
    const [showSettings, setShowSettings] = useState(false);
    const [calendarEvents, setCalendarEvents] = useState<CalendarEvent[]>([]);
    const [productivity, setProductivity] = useState<{ labels: string[]; completed: number[] }>({ labels: [], completed: [] });

    useEffect(() => {
        document.documentElement.classList.toggle('dark', dark);
        localStorage.setItem('remindly-theme', dark ? 'dark' : 'light');
    }, [dark]);

    useEffect(() => {
        axios.get<{ data: { status: string } }>('/api/v1/health').then(({ data }) => setHealth(data.data.status === 'ok' ? 'ok' : 'offline')).catch(() => setHealth('offline'));
        axios.get<{ data: DashboardStats }>('/api/v1/dashboard').then(({ data }) => setStats(data.data)).catch(() => {});
        axios.get<{ data: { unread: number } }>('/api/v1/notifications/unread-count').then(({ data }) => setUnread(data.data.unread)).catch(() => {});
        axios.get<{ data: CalendarEvent[] }>('/api/v1/calendar/events', { params: { start: new Date().toISOString(), end: new Date(Date.now() + 31 * 864e5).toISOString() } }).then(({ data }) => setCalendarEvents(data.data)).catch(() => {});
        axios.get<{ data: { labels: string[]; completed: number[] } }>('/api/v1/analytics/productivity', { params: { days: 30 } }).then(({ data }) => setProductivity(data.data)).catch(() => {});
    }, []);

    const addTask = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const today = new Date();
        const optimisticTitle = taskTitle;
        setStats((current) => ({
            ...current,
            total: current.total + 1,
            pending: current.pending + 1,
            today_tasks: [{ id: -Date.now(), title: optimisticTitle }, ...current.today_tasks],
        }));
        setTaskTitle('');
        setShowQuickAdd(false);
        const createdResponse = await axios.post<{ data: { id: number; title: string } }>('/api/v1/tasks', {
            title: optimisticTitle,
            start_at: today.toISOString(),
            deadline_at: new Date(today.getTime() + 60 * 60 * 1000).toISOString(),
            priority: 'medium',
            status: 'todo',
        });
        const createdTask = createdResponse.data.data;
        setStats((current) => ({
            ...current,
            today_tasks: current.today_tasks.map((task) =>
                task.id < 0 ? { id: createdTask.id, title: createdTask.title } : task,
            ),
        }));
    };

    const logout = async () => {
        await axios.post('/api/v1/logout');
        sessionStorage.removeItem('remindly-auth-user');
        setUser(null);
        navigate('/login');
    };

    return (
        <div className="app-shell">
            <aside className="sidebar">
                <div className="brand"><span className="brand-mark"><CheckSquare2 size={21} /></span>Remindly</div>
                <nav className="nav" aria-label="Navigasi utama">
                    {navItems.map(({ label, icon: Icon }, index) => (
                        <button className={`nav-item ${index === 0 ? 'active' : ''}`} key={label} onClick={() => label === 'Pengaturan' && setShowSettings(true)}><Icon size={18} /> {label}</button>
                    ))}
                </nav>
                <div className="sidebar-footer"><button className="nav-item" onClick={logout}><CircleUserRound size={18} /> Keluar</button></div>
            </aside>
            <main className="main">
                <header className="topbar">
                    <div className="greeting"><h1>Selamat datang, {user.name}</h1><p>Atur tugas dan jadwal Anda untuk hari yang lebih terorganisir.</p></div>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                        {health === 'ok' && <span className="health"><span className="health-dot" /> API aktif</span>}
                        <button className="icon-button" onClick={() => setDark((value) => !value)} aria-label="Ubah tema">{dark ? <Sun size={18} /> : <Moon size={18} />}</button>
                        <button className="icon-button notification-button" aria-label="Notifikasi"><Bell size={18} />{unread > 0 && <span className="notification-badge">{unread}</span>}</button>
                    </div>
                </header>
                <section className="dashboard-grid" aria-label="Ringkasan">
                    {[['Total tugas', stats.total], ['Selesai', stats.completed], ['Menunggu', stats.pending], ['Terlewat', stats.overdue]].map(([label, value]) => (
                        <article className="card stat-card" key={label as string}><div className="label">{label as string}</div><div className="value">{value as number}</div></article>
                    ))}
                </section>
                <section className="content-grid">
                    <article className="card panel">
                        <div className="panel-header"><h2>Tugas hari ini</h2><button className="icon-button" onClick={() => setShowQuickAdd(true)} aria-label="Tambah tugas"><Plus size={18} /></button></div>
                        {stats.today_tasks.length ? (
                            <div className="task-list">{stats.today_tasks.map((task) => <div className="task-row" key={task.id}><CheckSquare2 size={18} /><span>{task.title}</span></div>)}</div>
                        ) : <div className="empty-state"><div><CheckSquare2 size={34} /><p>Belum ada tugas hari ini.</p></div></div>}
                    </article>
                    <article className="card panel"><div className="panel-header"><h2>Pengingat mendatang</h2></div><div className="empty-state"><div><Bell size={34} /><p>Belum ada pengingat mendatang.</p></div></div></article>
                </section>
                <section className="content-grid">
                    <article className="card panel calendar-panel"><div className="panel-header"><h2>Kalender</h2></div><Suspense fallback={<div>Memuat kalender...</div>}><CalendarPanel events={calendarEvents} /></Suspense></article>
                    <article className="card panel"><div className="panel-header"><h2>Produktivitas 30 hari</h2></div><Suspense fallback={<div>Memuat chart...</div>}><ProductivityPanel labels={productivity.labels} completed={productivity.completed} /></Suspense></article>
                </section>
            </main>
            <nav className="mobile-nav" aria-label="Navigasi mobile">
                {navItems.map(({ label, icon: Icon }, index) => <button className={index === 0 ? 'active' : ''} key={label} aria-label={label}><Icon size={20} /></button>)}
            </nav>
            {showQuickAdd && (
                <div className="modal-backdrop" role="presentation" onMouseDown={(event) => event.target === event.currentTarget && setShowQuickAdd(false)}>
                    <section className="card modal" role="dialog" aria-modal="true" aria-label="Tambah tugas">
                        <div className="panel-header"><h2>Tambah tugas cepat</h2></div>
                        <form onSubmit={addTask} className="auth-form">
                            <label>Judul tugas<input value={taskTitle} onChange={(event) => setTaskTitle(event.target.value)} required autoFocus /></label>
                            <button className="primary-button" type="submit">Simpan tugas</button>
                        </form>
                    </section>
                </div>
            )}
            {showSettings && (
                <div className="modal-backdrop" role="presentation" onMouseDown={(event) => event.target === event.currentTarget && setShowSettings(false)}>
                    <section className="card modal settings-modal" role="dialog" aria-modal="true" aria-label="Pengaturan">
                        <div className="panel-header"><h2>Pengaturan</h2></div>
                        <div className="settings-list">
                            <button className="nav-item" onClick={() => setDark((value) => !value)}>{dark ? <Sun size={18} /> : <Moon size={18} />} Tema: {dark ? 'Gelap' : 'Terang'}</button>
                            <button className="nav-item" onClick={async () => {
                                const response = await axios.get<Blob>('/api/v1/settings/export', { responseType: 'blob' });
                                const url = window.URL.createObjectURL(response.data);
                                const link = document.createElement('a');
                                link.href = url;
                                link.download = 'remindly-export.json';
                                link.click();
                                window.URL.revokeObjectURL(url);
                            }}><CheckSquare2 size={18} /> Ekspor data JSON</button>
                            <button className="nav-item danger-action" onClick={async () => {
                                const password = window.prompt('Masukkan password untuk menghapus akun:');
                                if (!password) return;
                                try {
                                    await axios.delete('/api/v1/settings/account', { data: { password } });
                                    setUser(null);
                                    setShowSettings(false);
                                    navigate('/login');
                                } catch (caught) {
                                    window.alert(apiError(caught, 'Penghapusan akun gagal.'));
                                }
                            }}><CircleUserRound size={18} /> Hapus akun</button>
                        </div>
                    </section>
                </div>
            )}
        </div>
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
            <Route path="/login" element={user ? <Navigate to="/" /> : <AuthPage mode="login" />} />
            <Route path="/register" element={user ? <Navigate to="/" /> : <AuthPage mode="register" />} />
            <Route path="/forgot-password" element={user ? <Navigate to="/" /> : <AuthPage mode="forgot" />} />
            <Route path="/reset-password/:token" element={user ? <Navigate to="/" /> : <AuthPage mode="reset" />} />
            <Route path="/" element={user ? <Dashboard user={user} setUser={setUser} /> : <Navigate to="/login" />} />
        </Routes>
    );
}

const root = document.getElementById('app');
if (root) {
    createRoot(root).render(<React.StrictMode><BrowserRouter><App /></BrowserRouter></React.StrictMode>);
}
