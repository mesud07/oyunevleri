/* Public header component styles */
.public-header {
    background: #fff;
    border-bottom: 1px solid rgba(31,41,55,0.06);
}

.public-topbar {
    background: linear-gradient(90deg, #ff9a7a 0%, #ff7a59 100%);
    color: #fff;
    font-size: 13px;
}

.public-topbar-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 8px 0;
}

.public-topbar-left,
.public-topbar-right {
    display: flex;
    align-items: center;
    gap: 8px;
}

.public-topbar a {
    color: #fff;
    text-decoration: none;
    font-weight: 600;
}

.topbar-icon,
.topbar-social {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
}

.topbar-icon svg,
.topbar-social svg {
    width: 18px;
    height: 18px;
}

.topbar-social {
    background: rgba(255,255,255,0.18);
    border-radius: 50%;
}

.public-nav {
    display: flex;
    align-items: center;
    gap: 24px;
    padding: 16px 0;
}

.public-logo {
    font-family: "Baloo 2", cursive;
    font-size: 28px;
    font-weight: 700;
    letter-spacing: 0.2px;
    color: var(--ink);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.public-logo-mark {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: 3px solid #ff9a7a;
    box-shadow: inset 0 0 0 4px #ffd6c8;
}

.public-logo-accent {
    color: #ff7a59;
}

.public-links {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 28px;
    font-weight: 600;
    flex: 1;
    flex-wrap: wrap;
}

.public-links a {
    color: var(--ink);
    text-decoration: none;
    font-size: 15px;
}

.public-links a.active {
    color: #ff7a59;
}

.public-auth {
    display: flex;
    align-items: center;
    gap: 12px;
}

.public-menu-toggle {
    display: none;
    width: 42px;
    height: 42px;
    border-radius: 10px;
    border: 1px solid rgba(31,41,55,0.12);
    background: #fff;
    align-items: center;
    justify-content: center;
    gap: 4px;
    flex-direction: column;
    cursor: pointer;
}

.public-menu-toggle span {
    display: block;
    width: 18px;
    height: 2px;
    background: #ff7a59;
    border-radius: 999px;
}

.public-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 18px;
    border-radius: 999px;
    font-weight: 700;
    text-decoration: none;
    font-size: 14px;
    border: 1px solid transparent;
}

.public-btn.ghost {
    border-color: rgba(31,41,55,0.16);
    color: var(--ink);
    background: #fff;
}

.public-btn.primary {
    background: #ff7a59;
    color: #fff;
    border-color: #ff7a59;
    box-shadow: 0 10px 20px rgba(255,122,89,0.25);
}

.profile-wrap {
    position: relative;
}

.profile-toggle {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: #fff;
    border: 1px solid rgba(31,41,55,0.08);
    padding: 6px 12px 6px 8px;
    border-radius: 999px;
    cursor: pointer;
    font-weight: 600;
    color: var(--ink);
}

.profile-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #ffe3db;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #ff7a59;
}

.profile-icon svg {
    width: 16px;
    height: 16px;
}

.profile-menu {
    position: absolute;
    right: 0;
    top: calc(100% + 8px);
    background: #fff;
    border: 1px solid rgba(31,41,55,0.08);
    border-radius: 12px;
    min-width: 180px;
    box-shadow: 0 16px 35px rgba(31,41,55,0.18);
    display: none;
    overflow: hidden;
    z-index: 1000;
}

.profile-menu.is-open {
    display: block;
}

.profile-menu a {
    display: block;
    padding: 10px 12px;
    color: var(--ink);
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
}

.profile-menu a:hover {
    background: #f1f5f9;
}

@media (max-width: 980px) {
    .public-topbar-inner { flex-direction: row; align-items: center; justify-content: space-between; flex-wrap: nowrap; }
    .public-topbar-left, .public-topbar-right { white-space: nowrap; }
    .public-nav { flex-direction: row; align-items: center; justify-content: space-between; flex-wrap: wrap; }
    .public-menu-toggle { display: inline-flex; }
    .public-menu-toggle { margin-left: auto; }
    .public-links {
        width: 100%;
        flex: 0 0 100%;
        max-width: 100%;
        order: 3;
        flex-direction: column;
        align-items: flex-start;
        justify-content: flex-start;
        gap: 0;
        display: flex;
        padding: 6px 0 0;
        margin-top: 10px;
        background: #eef1f5;
        border-radius: 14px;
        overflow: hidden;
        border: 1px solid rgba(31,41,55,0.08);
        box-shadow: 0 16px 30px rgba(31,41,55,0.08);
        max-height: 0;
        opacity: 0;
        transform: translateY(-6px);
        pointer-events: none;
        transition: max-height 0.35s ease, opacity 0.25s ease, transform 0.25s ease;
    }
    .public-links.is-open {
        max-height: 480px;
        opacity: 1;
        transform: translateY(0);
        pointer-events: auto;
    }
    .public-links a {
        width: 100%;
        padding: 16px 18px;
        border-bottom: 1px solid rgba(31,41,55,0.08);
        font-size: 17px;
        color: #5b5f6a;
    }
    .public-auth {
        order: 4;
        width: 100%;
        flex: 0 0 100%;
        max-width: 100%;
        padding: 14px 18px 18px;
        background: #eef1f5;
        border-radius: 0 0 14px 14px;
        border-top: 1px solid rgba(31,41,55,0.08);
        max-height: 0;
        opacity: 0;
        transform: translateY(-6px);
        overflow: hidden;
        pointer-events: none;
        transition: max-height 0.35s ease, opacity 0.25s ease, transform 0.25s ease;
    }
    .public-links.is-open + .public-auth {
        max-height: 220px;
        opacity: 1;
        transform: translateY(0);
        pointer-events: auto;
    }
    .public-auth { gap: 10px; }
    .public-auth .public-btn { width: auto; }
}

@media (max-width: 768px) {
    .public-topbar-inner { gap: 8px; }
    .public-topbar-left, .public-topbar-right { gap: 6px; }
    .public-nav { gap: 12px; }
    .public-auth { width: 100%; justify-content: flex-start; flex-direction: column; }
    .public-btn { width: 100%; justify-content: center; }
}

@media (max-width: 520px) {
    .public-logo { font-size: 24px; }
    .public-logo-mark { width: 24px; height: 24px; }
    .public-topbar { font-size: 12px; }
    .public-topbar-inner { gap: 6px; }
    .public-topbar-left, .public-topbar-right { gap: 4px; }
    .public-topbar-left span, .public-topbar-right span { font-size: 12px; }
}
