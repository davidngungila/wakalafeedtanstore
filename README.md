
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="w3Vlfvw2Bh8VfqolGpMryy5mbgT3npZg5cr409mV">
    <title>Destinations · Tanzania Daily Tours & Safari</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        :root{
            --sand-50:#FBF7EF;
            --sand-100:#F4ECDC;
            --sand-200:#E9DCC0;
            --coffee-900:#2A1B10;
            --coffee-800:#3B2718;
            --coffee-700:#4D3422;
            --coffee-500:#7A5C42;
            --coffee-300:#A98968;
            --terracotta-600:#C2592B;
            --terracotta-500:#D06B3A;
            --terracotta-100:#F6E1D3;
            --acacia-600:#5E6E3F;
            --acacia-500:#7A8450;
            --acacia-100:#E2E7D4;
            --gold-500:#D4A24C;
            --gold-100:#F7E9CB;
            --ink:#241408;
            --ink-soft:#6B5A48;
            --line:#E4D7C2;
            --white:#FFFFFF;
            --danger:#B33A3A;
            --danger-100:#F6DCDA;
            --success:#3F6B3F;
            --radius-sm:8px;
            --radius-md:14px;
            --radius-lg:20px;
            --shadow-sm:0 1px 2px rgba(42,27,16,.08);
            --shadow-md:0 8px 24px rgba(42,27,16,.10);
            --shadow-lg:0 20px 48px rgba(42,27,16,.18);
            --sidebar-w:264px;
            --sidebar-w-collapsed:76px;
            --topbar-h:72px;
        }

        *{box-sizing:border-box;}
        html,body{height:100%;}
        body{
            margin:0;
            font-family:'Raleway',sans-serif;
            background:var(--sand-50);
            color:var(--ink);
            -webkit-font-smoothing:antialiased;
            overflow-x:hidden;
        }
        ::selection{background:var(--terracotta-100);color:var(--coffee-900);}
        h1,h2,h3,h4{font-family:'Raleway',sans-serif;margin:0;color:var(--coffee-900);letter-spacing:-0.01em;}
        p{margin:0;}
        a{color:inherit;text-decoration:none;}
        button{font-family:inherit;cursor:pointer;}
        input,select,textarea{font-family:inherit;}
        .mono{font-family:'Raleway',sans-serif;}
        ::-webkit-scrollbar{width:9px;height:9px;}
        ::-webkit-scrollbar-track{background:transparent;}
        ::-webkit-scrollbar-thumb{background:var(--coffee-300);border-radius:10px;}
        ::-webkit-scrollbar-thumb:hover{background:var(--coffee-500);}


        /* Sidebar */
        .sidebar{
            position:fixed;top:0;left:0;bottom:0;width:var(--sidebar-w);z-index:200;
            background:var(--coffee-900);
            background-image:radial-gradient(circle at 0% 0%, rgba(212,162,76,.10), transparent 55%);
            display:flex;flex-direction:column;
            transition:width .25s ease, transform .25s ease;
            border-right:1px solid rgba(255,255,255,.06);
        }
        .sidebar.collapsed{width:var(--sidebar-w-collapsed);}
        .sb-brand{
            display:flex;align-items:center;gap:12px;padding:22px 20px;
            border-bottom:1px solid rgba(255,255,255,.08);min-height:var(--topbar-h);
        }
        .sb-mark{
            width:38px;height:38px;border-radius:10px;flex:none;
            background:linear-gradient(155deg,var(--terracotta-600),var(--gold-500));
            display:flex;align-items:center;justify-content:center;box-shadow:var(--shadow-sm);
        }
        .sb-mark svg{width:21px;height:21px;}
        .sb-brand-text{overflow:hidden;white-space:nowrap;}
        .sb-brand-text strong{display:block;color:#fff;font-family:'Raleway',sans-serif;font-size:15.5px;line-height:1.2;}
        .sb-brand-text span{display:block;color:var(--gold-500);font-size:11px;letter-spacing:.06em;text-transform:uppercase;font-weight:600;}
        .sidebar.collapsed .sb-brand-text{display:none;}
        .sb-nav{flex:1;overflow-y:auto;padding:16px 12px;}
        .sb-section-label{
            color:rgba(255,255,255,.32);font-size:10.5px;font-weight:700;letter-spacing:.09em;
            text-transform:uppercase;padding:14px 12px 8px;
        }
        .sidebar.collapsed .sb-section-label{display:none;}
        .sb-item{
            display:flex;align-items:center;gap:13px;padding:11px 12px;border-radius:10px;
            color:rgba(255,255,255,.62);font-size:14px;font-weight:500;margin-bottom:2px;
            position:relative;transition:background .15s,color .15s;white-space:nowrap;
        }
        .sb-item:hover{background:rgba(255,255,255,.06);color:#fff;}
        .sb-item.active{background:rgba(212,162,76,.16);color:var(--gold-500);}
        .sb-item.active::before{
            content:"";position:absolute;left:-12px;top:8px;bottom:8px;width:3px;border-radius:3px;
            background:var(--gold-500);
        }
        .sb-item svg{width:19px;height:19px;flex:none;}
        .sb-item .badge{
            margin-left:auto;background:var(--terracotta-600);color:#fff;font-size:11px;font-weight:700;
            padding:1px 7px;border-radius:20px;
        }
        .sidebar.collapsed .sb-item span:not(.badge){display:none;}
        .sidebar.collapsed .sb-item .badge{display:none;}
        .sidebar.collapsed .sb-item{justify-content:center;}
        .sb-drop{position:relative;}
        .sb-drop-toggle{width:100%;cursor:pointer;background:none;border:none;font-family:inherit;}
        .sb-drop-toggle .chev{margin-left:auto;opacity:.55;transition:transform .25s ease;width:15px;height:15px;flex:none;}
        .sb-drop.open .sb-drop-toggle .chev{transform:rotate(180deg);}
        .sb-drop-menu{display:none;margin:2px 0 4px;padding-left:12px;}
        .sb-drop.open .sb-drop-menu{display:block;}
        .sidebar.collapsed .sb-drop-menu{display:none;}
        .sb-drop-sub{display:flex;align-items:center;gap:9px;padding:9px 12px;border-radius:8px;margin-bottom:1px;color:rgba(255,255,255,.55);font-size:13px;font-weight:500;text-decoration:none;transition:background .15s ease,color .15s ease;}
        .sb-drop-sub:hover{color:#fff;background:rgba(255,255,255,.06);}
        .sb-drop-sub.active{color:var(--gold-500);background:rgba(212,162,76,.12);}
        .sb-drop-sub svg{width:14px;height:14px;flex:none;}
        .sb-footer{padding:14px 20px 20px;border-top:1px solid rgba(255,255,255,.08);}
        .sb-user{display:flex;align-items:center;gap:11px;}
        .sb-avatar{
            width:36px;height:36px;border-radius:50%;flex:none;
            background:linear-gradient(155deg,var(--acacia-500),var(--acacia-600));
            display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:13px;
        }
        .sb-user-text{overflow:hidden;white-space:nowrap;}
        .sb-user-text strong{display:block;color:#fff;font-size:13.5px;}
        .sb-user-text span{display:block;color:rgba(255,255,255,.5);font-size:11.5px;}
        .sidebar.collapsed .sb-user-text{display:none;}


        /* Main */
        .main{margin-left:var(--sidebar-w);transition:margin-left .25s ease;min-height:100vh;display:flex;flex-direction:column;}
        .sidebar.collapsed ~ .main{margin-left:var(--sidebar-w-collapsed);}
        .topbar{
            position:sticky;top:0;z-index:100;height:var(--topbar-h);
            background:rgba(251,247,239,.86);backdrop-filter:blur(10px);
            border-bottom:1px solid var(--line);
            display:flex;align-items:center;gap:16px;padding:0 28px;
        }
        .tb-toggle{
            width:38px;height:38px;border-radius:10px;border:1.5px solid var(--line);background:var(--white);
            display:flex;align-items:center;justify-content:center;flex:none;
        }
        .tb-toggle svg{width:18px;height:18px;color:var(--coffee-700);}
        .tb-toggle:hover{background:var(--sand-100);}
        .tb-search{
            flex:1;max-width:420px;display:flex;align-items:center;gap:10px;
            background:var(--white);border:1.5px solid var(--line);border-radius:11px;padding:9px 14px;
        }
        .tb-search svg{width:17px;height:17px;color:var(--ink-soft);flex:none;}
        .tb-search input{border:none;outline:none;background:transparent;font-size:14px;width:100%;color:var(--ink);}
        .tb-right{margin-left:auto;display:flex;align-items:center;gap:10px;}
        .tb-iconbtn{
            width:40px;height:40px;border-radius:11px;border:1.5px solid var(--line);background:var(--white);
            display:flex;align-items:center;justify-content:center;position:relative;color:var(--coffee-700);
        }
        .tb-iconbtn:hover{background:var(--sand-100);}
        .tb-iconbtn svg{width:18px;height:18px;}
        .tb-dot{
            position:absolute;top:7px;right:7px;width:8px;height:8px;border-radius:50%;
            background:var(--terracotta-600);border:2px solid var(--sand-50);
        }
        .tb-live{
            display:flex;align-items:center;gap:7px;background:var(--acacia-100);color:var(--acacia-600);
            padding:7px 13px;border-radius:20px;font-size:12.5px;font-weight:700;
        }
        .tb-live::before{
            content:"";width:7px;height:7px;border-radius:50%;background:var(--acacia-600);box-shadow:0 0 0 0 rgba(94,110,63,.5);animation:pulse 2s infinite;
        }
        @keyframes pulse{
            0%{box-shadow:0 0 0 0 rgba(94,110,63,.45);}
            70%{box-shadow:0 0 0 7px rgba(94,110,63,0);}
            100%{box-shadow:0 0 0 0 rgba(94,110,63,0);}
        }
        .view-wrap{padding:28px;flex:1;}
        .view{display:none;animation:fadeUp .35s ease;}
        .view.active{display:block;}
        @keyframes fadeUp{from{opacity:0;transform:translateY(8px);}to{opacity:1;transform:translateY(0);}}
        .view-head{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:24px;flex-wrap:wrap;}
        .view-head h2{font-size:27px;}
        .view-head .sub{color:var(--ink-soft);font-size:14px;margin-top:5px;}
        .view-actions{display:flex;gap:10px;flex-wrap:wrap;}


        /* Stats */
        .stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:24px;}
        .stat-card{
            background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);
            padding:20px 20px 18px;box-shadow:var(--shadow-sm);position:relative;overflow:hidden;
        }
        .stat-card::after{content:"";position:absolute;right:-20px;top:-20px;width:90px;height:90px;border-radius:50%;background:var(--stat-tint,var(--terracotta-100));opacity:.5;}
        .stat-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
        .stat-icon{width:40px;height:40px;border-radius:11px;display:flex;align-items:center;justify-content:center;background:var(--stat-tint,var(--terracotta-100));color:var(--stat-fg,var(--terracotta-600));position:relative;}
        .stat-icon svg{width:20px;height:20px;}
        .stat-trend{font-size:12px;font-weight:700;padding:3px 8px;border-radius:20px;}
        .stat-trend.up{color:var(--success);background:var(--acacia-100);}
        .stat-trend.down{color:var(--danger);background:var(--danger-100);}
        .stat-value{font-family:'Raleway',sans-serif;font-size:30px;color:var(--coffee-900);position:relative;}
        .stat-label{font-size:13px;color:var(--ink-soft);margin-top:4px;position:relative;}

        .panel-grid{display:grid;grid-template-columns:1.55fr 1fr;gap:18px;margin-bottom:24px;}
        .panel{background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);overflow:hidden;}
        .panel-head{display:flex;align-items:center;justify-content:space-between;padding:18px 20px 14px;border-bottom:1px solid var(--line);}
        .panel-head h3{font-size:16.5px;}
        .panel-head .link{font-size:12.5px;font-weight:700;color:var(--terracotta-600);cursor:pointer;}
        .panel-body{padding:18px 20px 20px;}

        /* Bar Chart */
        .bars{display:flex;align-items:flex-end;gap:10px;height:170px;padding-top:10px;}
        .bar-col{flex:1;display:flex;flex-direction:column;align-items:center;gap:8px;height:100%;justify-content:flex-end;}
        .bar{
            width:100%;max-width:30px;border-radius:6px 6px 2px 2px;
            background:linear-gradient(180deg,var(--terracotta-500),var(--terracotta-600));
            transition:height .6s cubic-bezier(.2,.8,.2,1);position:relative;
        }
        .bar-col:nth-child(even) .bar{background:linear-gradient(180deg,var(--gold-500),#bb8636);}
        .bar-label{font-size:11px;color:var(--ink-soft);font-weight:600;}

        .donut-wrap{display:flex;align-items:center;gap:18px;}
        .legend{display:flex;flex-direction:column;gap:10px;flex:1;}
        .legend-item{display:flex;align-items:center;gap:9px;font-size:13px;}
        .legend-dot{width:10px;height:10px;border-radius:3px;flex:none;}
        .legend-item b{margin-left:auto;color:var(--coffee-900);}

        .activity-list{display:flex;flex-direction:column;gap:3px;}
        .activity-row{display:flex;gap:12px;padding:11px 0;border-bottom:1px dashed var(--line);}
        .activity-row:last-child{border-bottom:none;}
        .activity-ico{width:34px;height:34px;border-radius:9px;flex:none;display:flex;align-items:center;justify-content:center;background:var(--sand-100);color:var(--coffee-700);}
        .activity-ico svg{width:16px;height:16px;}
        .activity-text{font-size:13.5px;line-height:1.4;}
        .activity-text b{color:var(--coffee-900);}
        .activity-time{font-size:11.5px;color:var(--ink-soft);margin-top:2px;}


        /* Tables */
        .table-card{background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);overflow:hidden;}
        .table-toolbar{display:flex;align-items:center;gap:10px;padding:16px 18px;border-bottom:1px solid var(--line);flex-wrap:wrap;}
        .chip-filters{display:flex;gap:8px;flex-wrap:wrap;}
        .chip{padding:7px 14px;border-radius:20px;font-size:12.5px;font-weight:600;background:var(--sand-100);color:var(--coffee-700);border:1px solid transparent;cursor:pointer;display:inline-flex;align-items:center;text-decoration:none;}
        .chip.active{background:var(--coffee-900);color:#fff;}
        .table-search{display:flex;align-items:center;gap:8px;background:var(--sand-50);border:1.5px solid var(--line);border-radius:10px;padding:8px 12px;margin-left:auto;min-width:200px;}
        .table-search svg{width:15px;height:15px;color:var(--ink-soft);}
        .table-search input{border:none;background:transparent;outline:none;font-size:13.5px;width:100%;}
        .table-scroll{overflow-x:auto;}
        .table-pager{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 18px;border-top:1px solid var(--line);flex-wrap:wrap;}
        .pager-pages{display:flex;gap:6px;align-items:center;flex-wrap:wrap;}
        .pager-pages a,.pager-pages span.page{min-width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;padding:0 8px;border:1px solid var(--line);border-radius:8px;font-size:12.5px;font-weight:600;color:var(--coffee-700);text-decoration:none;background:var(--white);}
        .pager-pages a:hover{border-color:var(--gold-500);color:var(--terracotta-600);}
        .pager-pages span.active{background:var(--coffee-900);color:#fff;border-color:var(--coffee-900);}
        table{width:100%;border-collapse:collapse;min-width:680px;}
        thead th{
            text-align:left;font-size:11.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--ink-soft);
            padding:12px 18px;border-bottom:1px solid var(--line);background:var(--sand-50);font-weight:700;white-space:nowrap;
        }
        tbody td{padding:14px 18px;border-bottom:1px solid var(--line);font-size:13.5px;color:var(--coffee-900);}
        tbody tr:last-child td{border-bottom:none;}
        .table-pagination{padding:16px 18px;border-top:1px solid var(--line);display:flex;align-items:center;justify-content:center;gap:8px;}
        .table-pagination a,.table-pagination span{padding:8px 14px;border-radius:8px;font-size:12.5px;font-weight:600;border:1px solid var(--line);background:var(--white);color:var(--coffee-700);text-decoration:none;transition:all .2s;}
        .table-pagination a:hover{background:var(--sand-100);border-color:var(--coffee-300);}
        .table-pagination .active{background:var(--coffee-900);color:#fff;border-color:var(--coffee-900);}
        .table-pagination .disabled{opacity:.5;cursor:not-allowed;}
        tbody tr{transition:background .12s;}
        tbody tr:hover{background:var(--sand-50);}
        .cell-main{display:flex;align-items:center;gap:11px;}
        .thumb{width:42px;height:42px;border-radius:9px;object-fit:cover;flex:none;background:var(--sand-200);}
        .cell-title{font-weight:600;color:var(--coffee-900);}
        .cell-sub{font-size:12px;color:var(--ink-soft);margin-top:1px;}
        .tag{display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:20px;font-size:11.5px;font-weight:700;white-space:nowrap;}
        .tag-green{background:var(--acacia-100);color:var(--acacia-600);}
        .tag-gold{background:var(--gold-100);color:#8a6418;}
        .tag-red{background:var(--danger-100);color:var(--danger);}
        .tag-grey{background:var(--sand-200);color:var(--ink-soft);}
        .tag-terracotta{background:var(--terracotta-100);color:var(--terracotta-600);}
        .row-actions{display:flex;gap:6px;justify-content:flex-end;}
        .row-actions button{width:32px;height:32px;border-radius:8px;border:1px solid var(--line);background:var(--white);display:flex;align-items:center;justify-content:center;color:var(--coffee-700);}
        .row-actions button:hover{background:var(--sand-100);}
        .row-actions button svg{width:14.5px;height:14.5px;}
        .row-actions .danger:hover{background:var(--danger-100);color:var(--danger);border-color:var(--danger-100);}
        .row-actions .warn:hover{background:var(--gold-100);color:#8a6418;border-color:var(--gold-100);}
        .pwd-wrap{position:relative;}
        .pwd-wrap input{padding-right:42px;}
        .pwd-toggle{position:absolute;right:4px;top:50%;transform:translateY(-50%);width:32px;height:32px;border:none;background:transparent;cursor:pointer;font-size:14px;opacity:.7;border-radius:8px;}
        .pwd-toggle:hover{opacity:1;background:var(--sand-200);}
        .input-icon-wrap{position:relative;}
        .input-icon-wrap .input-icon{position:absolute;left:14px;top:0;bottom:0;margin:auto 0;width:16px;height:16px;color:var(--coffee-300);pointer-events:none;}
        .field .input-icon-wrap input,.field .input-icon-wrap textarea{padding-left:42px;}
        .settings-section{display:flex;align-items:center;gap:9px;margin:24px 0 4px;padding-top:8px;}
        .settings-section:first-of-type{margin-top:0;padding-top:0;}
        .settings-section::before{content:'';width:22px;height:3px;border-radius:2px;background:var(--gold-500);flex:none;}
        .settings-section h4{margin:0;font-size:12.5px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--coffee-700);}
        .empty-state{padding:60px 20px;text-align:center;color:var(--ink-soft);}
        .empty-state svg{width:46px;height:46px;color:var(--coffee-300);margin-bottom:12px;}
        .empty-state h4{margin-bottom:5px;color:var(--coffee-800);}
        .empty-state p{font-size:13.5px;}


        /* Buttons */
        .btn{
            display:inline-flex;align-items:center;justify-content:center;gap:8px;
            padding:12px 20px;border-radius:var(--radius-sm);border:none;
            font-weight:600;font-size:14.5px;transition:transform .12s, box-shadow .12s, background .15s;
        }
        .btn:active{transform:translateY(1px);}
        .btn-primary{background:var(--terracotta-600);color:#fff;box-shadow:0 6px 16px rgba(194,89,43,.32);}
        .btn-primary:hover{background:var(--terracotta-500);}
        .btn-ghost{background:transparent;color:var(--coffee-700);border:1.5px solid var(--line);}
        .btn-ghost:hover{background:var(--sand-100);}
        .btn-soft{background:var(--sand-100);color:var(--coffee-800);}
        .btn-soft:hover{background:var(--sand-200);}
        .btn-danger{background:var(--danger-100);color:var(--danger);}
        .btn-danger:hover{background:#efc6c2;}
        .btn-sm{padding:8px 13px;font-size:13px;}


        /* Gallery */
        .gallery-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:16px;}
        .gallery-card{position:relative;border-radius:var(--radius-md);overflow:hidden;aspect-ratio:1/1;box-shadow:var(--shadow-sm);border:1px solid var(--line);background:var(--sand-200);}
        .gallery-card img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .35s;}
        .gallery-card:hover img{transform:scale(1.06);}
        .gallery-overlay{
            position:absolute;inset:0;background:linear-gradient(180deg,rgba(36,20,8,0) 45%,rgba(36,20,8,.82));
            display:flex;flex-direction:column;justify-content:flex-end;padding:12px;opacity:0;transition:opacity .2s;
        }
        .gallery-card:hover .gallery-overlay{opacity:1;}
        .gallery-cap{color:#fff;font-size:12.5px;font-weight:600;margin-bottom:8px;line-height:1.3;}
        .gallery-actions{display:flex;gap:6px;}
        .gallery-actions button{flex:1;padding:6px;border-radius:7px;border:none;background:rgba(255,255,255,.18);color:#fff;backdrop-filter:blur(4px);font-size:11.5px;font-weight:600;display:flex;align-items:center;justify-content:center;gap:5px;}
        .gallery-actions button:hover{background:rgba(255,255,255,.3);}
        .gallery-badge{position:absolute;top:10px;left:10px;background:rgba(36,20,8,.65);color:#fff;font-size:10.5px;font-weight:700;padding:4px 9px;border-radius:20px;text-transform:uppercase;letter-spacing:.04em;backdrop-filter:blur(4px);}
        .add-tile{
            border:2px dashed var(--coffee-300);border-radius:var(--radius-md);aspect-ratio:1/1;
            display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;color:var(--coffee-500);
            background:var(--sand-100);font-size:12.5px;font-weight:600;cursor:pointer;
        }
        .add-tile:hover{background:var(--sand-200);border-color:var(--terracotta-500);color:var(--terracotta-600);}
        .add-tile svg{width:26px;height:26px;}


        /* Reviews */
        .review-card{display:flex;gap:14px;background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);padding:18px;box-shadow:var(--shadow-sm);margin-bottom:14px;}
        .review-avatar{width:44px;height:44px;border-radius:50%;flex:none;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:15px;background:linear-gradient(155deg,var(--terracotta-600),var(--gold-500));}
        .review-top{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:4px;flex-wrap:wrap;}
        .review-name{font-weight:700;color:var(--coffee-900);font-size:14.5px;}
        .review-tour{font-size:12px;color:var(--ink-soft);margin-top:1px;}
        .review-text{font-size:13.8px;color:var(--coffee-800);line-height:1.55;margin:8px 0 10px;}
        .review-actions{display:flex;gap:8px;}


        /* Messages */
        .msg-layout{display:grid;grid-template-columns:340px 1fr;gap:18px;align-items:start;}
        .msg-list{background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);overflow:hidden;box-shadow:var(--shadow-sm);}
        .msg-item{display:flex;gap:11px;padding:14px 16px;border-bottom:1px solid var(--line);cursor:pointer;position:relative;}
        .msg-item:hover{background:var(--sand-50);}
        .msg-item.active{background:var(--terracotta-100);}
        .msg-item.unread::before{content:"";position:absolute;left:6px;top:50%;transform:translateY(-50%);width:7px;height:7px;border-radius:50%;background:var(--terracotta-600);}
        .msg-item-name{font-weight:700;font-size:13.5px;color:var(--coffee-900);}
        .msg-item-prev{font-size:12px;color:var(--ink-soft);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:230px;}
        .msg-item-time{font-size:10.5px;color:var(--ink-soft);margin-left:auto;flex:none;}
        .msg-detail{background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);padding:24px;min-height:420px;display:flex;flex-direction:column;}
        .msg-detail-head{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1px solid var(--line);padding-bottom:16px;margin-bottom:16px;}
        .msg-detail-body{font-size:14.5px;line-height:1.7;color:var(--coffee-800);flex:1;}
        .msg-detail-foot{display:flex;gap:10px;margin-top:20px;padding-top:16px;border-top:1px solid var(--line);}


        /* Settings */
        .settings-grid{display:grid;grid-template-columns:240px 1fr;gap:24px;align-items:start;}
        .settings-grid-single{grid-template-columns:1fr;}
        .settings-nav{display:flex;flex-direction:column;gap:3px;background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);padding:10px;box-shadow:var(--shadow-sm);}
        .settings-nav button{
            display:flex;align-items:center;gap:10px;text-align:left;padding:11px 13px;border-radius:9px;border:none;background:transparent;
            font-size:13.8px;font-weight:600;color:var(--coffee-700);cursor:pointer;
        }
        .settings-nav button.active{background:var(--sand-100);color:var(--terracotta-600);}
        .settings-nav button svg{width:17px;height:17px;}
        .settings-panel{background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);padding:26px;}
        .settings-panel h3{font-size:17px;margin-bottom:18px;}
        .settings-pane{display:none;}
        .settings-pane.active{display:block;}
        .field{margin-bottom:16px;}
        .field label{display:block;font-size:12.5px;font-weight:600;color:var(--coffee-700);margin-bottom:7px;text-transform:uppercase;letter-spacing:.04em;}
        .field input,.field select,.field textarea{
            width:100%;padding:12px 14px;border:1.5px solid var(--line);border-radius:var(--radius-sm);
            background:var(--white);font-size:14.5px;color:var(--ink);transition:border-color .15s, box-shadow .15s;
        }
        .field input:focus,.field select:focus,.field textarea:focus{
            outline:none;border-color:var(--terracotta-500);box-shadow:0 0 0 3px var(--terracotta-100);
        }
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
        .toggle-row{display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid var(--line);}
        .toggle-row:last-child{border-bottom:none;}
        .toggle-text strong{display:block;font-size:14px;color:var(--coffee-900);margin-bottom:2px;}
        .toggle-text span{font-size:12.5px;color:var(--ink-soft);}
        .switch{width:42px;height:24px;border-radius:20px;background:var(--sand-200);position:relative;flex:none;border:none;cursor:pointer;transition:background .2s;}
        .switch::after{content:"";position:absolute;top:3px;left:3px;width:18px;height:18px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.25);transition:transform .2s;}
        .switch.on{background:var(--acacia-500);}
        .switch.on::after{transform:translateX(18px);}


        /* Modal */
        .modal-backdrop{
            position:fixed;inset:0;background:rgba(36,20,8,.5);backdrop-filter:blur(2px);
            display:none;align-items:flex-start;justify-content:center;z-index:400;padding:40px 20px;overflow-y:auto;
        }
        .modal-backdrop.show{display:flex;}
        .modal{
            background:var(--sand-50);border-radius:var(--radius-lg);width:100%;max-width:560px;
            box-shadow:var(--shadow-lg);animation:riseIn .3s cubic-bezier(.2,.8,.2,1);margin:auto;
        }
        @keyframes riseIn{from{opacity:0;transform:translateY(14px);}to{opacity:1;transform:translateY(0);}}
        .modal-head{display:flex;align-items:center;justify-content:space-between;padding:22px 24px;border-bottom:1px solid var(--line);}
        .modal-head h3{font-size:19px;}
        .modal-close{width:34px;height:34px;border-radius:9px;border:1px solid var(--line);background:var(--white);display:flex;align-items:center;justify-content:center;}
        .modal-body{padding:22px 24px;max-height:60vh;overflow-y:auto;}
        .modal-foot{display:flex;justify-content:flex-end;gap:10px;padding:18px 24px;border-top:1px solid var(--line);}


        /* Toast */
        #toastHost{position:fixed;bottom:24px;right:24px;z-index:600;display:flex;flex-direction:column;gap:10px;}
        .toast{
            background:var(--coffee-900);color:#fff;padding:13px 18px;border-radius:11px;font-size:13.5px;font-weight:600;
            box-shadow:var(--shadow-lg);display:flex;align-items:center;gap:10px;min-width:240px;animation:toastIn .3s ease;
        }
        .toast.success{background:var(--acacia-600);}
        .toast.error{background:var(--danger);}
        .toast svg{width:17px;height:17px;flex:none;}
        @keyframes toastIn{from{opacity:0;transform:translateX(20px);}to{opacity:1;transform:translateX(0);}}


        /* Responsive */
        .mobile-overlay{position:fixed;inset:0;background:rgba(36,20,8,.45);z-index:190;display:none;}
        .mobile-overlay.show{display:block;}
        @media (max-width:1180px){
            .stat-grid{grid-template-columns:repeat(2,1fr);}
            .panel-grid{grid-template-columns:1fr;}
            .settings-grid{grid-template-columns:1fr;}
            .msg-layout{grid-template-columns:1fr;}
        }
        @media (max-width:900px){
            .sidebar{transform:translateX(-100%);width:var(--sidebar-w);z-index:300;}
            .sidebar.mobile-open{transform:translateX(0);}
            .main{margin-left:0 !important;}
            .tb-search{display:none;}
        }
        @media (max-width:640px){
            .stat-grid{grid-template-columns:1fr;}
            .view-wrap{padding:16px;}
            .topbar{padding:0 14px;gap:10px;}
            .form-row{grid-template-columns:1fr;}
            .tb-live span{display:none;}
            .view-head h2{font-size:22px;}
        }
        @media (prefers-reduced-motion:reduce){
            *{animation-duration:.001ms !important;transition-duration:.001ms !important;}
        }
    </style>
</head>
<body>
    <div id="app" class="show">
        <div class="mobile-overlay" id="mobileOverlay" onclick="closeMobileSidebar()"></div>
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sb-brand">
                <img src="https://res.cloudinary.com/aenplcpl/image/upload/f_auto,q_auto,w_200/v1782890324/safari-logo-white_bexcal.png" alt="Tanzania Daily Tours & Safari" style="width: 38px; height: 38px; border-radius: 10px; flex: none;">
                <div class="sb-brand-text">
                    <strong>Tanzania Daily</strong>
                    <span>Tours & Safari · CMS</span>
                </div>
            </div>
            <nav class="sb-nav">
                <div class="sb-section-label">Overview</div>
                <a href="https://tanzaniadailytoursandsafari.com/live" class="sb-item ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="9" rx="1.5"></rect>
                        <rect x="14" y="3" width="7" height="5" rx="1.5"></rect>
                        <rect x="14" y="12" width="7" height="9" rx="1.5"></rect>
                        <rect x="3" y="16" width="7" height="5" rx="1.5"></rect>
                    </svg>
                    <span>Dashboard</span>
                </a>
                <div class="sb-section-label">Content</div>
                <a href="https://tanzaniadailytoursandsafari.com/live/destinations" class="sb-item active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 11 12 4l9 7"></path>
                        <path d="M5 10v10h14V10"></path>
                        <path d="M9 20v-6h6v6"></path>
                    </svg>
                    <span>Destinations</span>
                    <span class="badge" id="navDestCount">62</span>
                </a>
                <a href="https://tanzaniadailytoursandsafari.com/live/gallery" class="sb-item ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.8"></circle>
                        <path d="m21 15-5-5L5 21"></path>
                    </svg>
                    <span>Gallery</span>
                </a>
                <a href="https://tanzaniadailytoursandsafari.com/live/reviews" class="sb-item ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2.5 15.1 9 22 10 17 15 18.2 22 12 18.6 5.8 22 7 15 2 10 8.9 9"></polygon>
                    </svg>
                    <span>Reviews</span>
                    <span class="badge" id="navReviewCount">0</span>
                </a>
                <div class="sb-section-label">Operations</div>
                <a href="https://tanzaniadailytoursandsafari.com/live/bookings" class="sb-item ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="17" rx="2"></rect>
                        <path d="M16 2v4M8 2v4M3 9h18"></path>
                        <path d="m9 14 2 2 4-4"></path>
                    </svg>
                    <span>Bookings</span>
                    <span class="badge" id="navBookingCount">1</span>
                </a>
                <a href="https://tanzaniadailytoursandsafari.com/live/payments" class="sb-item ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                        <line x1="2" y1="10" x2="22" y2="10"></line>
                    </svg>
                    <span>Payments</span>
                    <span class="badge" id="navPaymentCount">5</span>
                </a>
                <a href="https://tanzaniadailytoursandsafari.com/live/messages" class="sb-item ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2Z"></path>
                    </svg>
                    <span>Messages</span>
                    <span class="badge" id="navMsgCount">0</span>
                </a>
                <div class="sb-section-label">System</div>
                <div class="sb-drop ">
                    <button type="button" class="sb-item sb-drop-toggle " onclick="toggleSbDrop(this)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1.04 1.56V21a2 2 0 0 1-4 0v-.09A1.7 1.7 0 0 0 9 19.4a1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.56-1.04H3a2 2 0 0 1 0-4h.09A1.7 1.7 0 0 0 4.6 9a1.7 1.7 0 0 0-.34-1.87l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1.04-1.56V3a2 2 0 0 1 4 0v.09A1.7 1.7 0 0 0 15 4.6a1.7 1.7 0 0 0 1.87-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9a1.7 1.7 0 0 0 1.56 1.04H21a2 2 0 0 1 0 4h-.09A1.7 1.7 0 0 0 19.4 15Z"></path>
                        </svg>
                        <span>Site Settings</span>
                        <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <div class="sb-drop-menu">
                        <a href="https://tanzaniadailytoursandsafari.com/live/settings?pane=general" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                                <rect x="9" y="9" width="6" height="6"></rect>
                            </svg>
                            General
                        </a>
                        <a href="https://tanzaniadailytoursandsafari.com/live/settings?pane=brand" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="13.5" cy="6.5" r=".5"></circle>
                                <circle cx="17.5" cy="10.5" r=".5"></circle>
                                <circle cx="8.5" cy="7.5" r=".5"></circle>
                                <circle cx="6.5" cy="12.5" r=".5"></circle>
                                <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C22 6.012 17.461 2 12 2Z"></path>
                            </svg>
                            Brand & Colors
                        </a>
                        <a href="https://tanzaniadailytoursandsafari.com/live/settings?pane=contact" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92Z"></path>
                            </svg>
                            Contact & Social
                        </a>
                        <a href="https://tanzaniadailytoursandsafari.com/live/settings?pane=notifications" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path>
                                <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path>
                            </svg>
                            Notifications
                        </a>
                        <a href="https://tanzaniadailytoursandsafari.com/live/users" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            Admin Users
                        </a>
                        <a href="https://tanzaniadailytoursandsafari.com/live/settings?pane=payments" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                                <line x1="2" y1="10" x2="22" y2="10"></line>
                            </svg>
                            Payments
                        </a>
                        <a href="https://tanzaniadailytoursandsafari.com/live/settings?pane=mail" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                            </svg>
                            Mail
                        </a>
                    </div>
                </div>
            </nav>
            <div class="sb-footer">
                <a href="https://tanzaniadailytoursandsafari.com/live/profile" class="sb-user" style="text-decoration:none;">
                    <div class="sb-avatar">
                        JE
                    </div>
                    <div class="sb-user-text">
                        <strong>Jeremia Developer</strong>
                        <span>jeremiat449@gmail.com</span>
                    </div>
                </a>
            </div>
        </aside>

        <!-- Main -->
        <div class="main" id="mainArea">
            <header class="topbar">
                <button class="tb-toggle" onclick="toggleSidebar()" aria-label="Toggle sidebar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="4" y1="7" x2="20" y2="7"></line>
                        <line x1="4" y1="12" x2="20" y2="12"></line>
                        <line x1="4" y1="17" x2="20" y2="17"></line>
                    </svg>
                </button>
                <div class="tb-search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" placeholder="Search tours, bookings, guests…">
                </div>
                <div class="tb-right">
                    <div class="tb-live"><span>Site live</span></div>
                    <a class="tb-iconbtn" href="https://tanzaniadailytoursandsafari.com" target="_blank" rel="noopener" aria-label="View live site" title="View live site">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                            <path d="M15 3h6v6"></path>
                            <path d="M10 14 21 3"></path>
                        </svg>
                    </a>
                    <form method="POST" action="https://tanzaniadailytoursandsafari.com/live/logout" style="display:inline;">
                        <input type="hidden" name="_token" value="w3Vlfvw2Bh8VfqolGpMryy5mbgT3npZg5cr409mV" autocomplete="off">                        <button type="submit" class="tb-iconbtn" aria-label="Log out" title="Log out">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16 17 21 12 16 7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                        </button>
                    </form>
                </div>
            </header>

            <div class="view-wrap">
                <!-- Session Messages -->
                
                <!-- Content -->
                <div class="view active">
    <div class="view-head">
        <div>
            <h2>Destinations & Tours</h2>
            <p class="sub">Manage day trips and multi-day safari packages shown on the live site.</p>
        </div>
        <div class="view-actions">
            <button class="btn btn-primary" onclick="openDestinationModal()">+ Add destination</button>
        </div>
    </div>

    <div class="table-card">
        <div class="table-toolbar">
            <div class="chip-filters" id="destFilterChips">
                <button class="chip active" data-filter="all" onclick="setDestFilter('all')">All</button>
                <button class="chip" data-filter="Day Trip" onclick="setDestFilter('Day Trip')">Day Trips</button>
                <button class="chip" data-filter="Multi-Day Safari" onclick="setDestFilter('Multi-Day Safari')">Multi-Day Safaris</button>
                <button class="chip" data-filter="Published" onclick="setDestFilter('Published')">Published</button>
                <button class="chip" data-filter="Draft" onclick="setDestFilter('Draft')">Drafts</button>
            </div>
            <div class="table-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" placeholder="Search destinations…" oninput="filterDestinations(this.value)">
            </div>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Destination</th>
                        <th>Category</th>
                        <th>Duration</th>
                        <th>Adult Price</th>
                        <th>Child Price</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="destinationsBody">
                                                            <tr data-id="69" data-category="Day Trip" data-status="Published" data-name="1-day horseback wildlife safari experience">
                        <td>
                            <div class="cell-main">
                                <img class="thumb" src="https://res.cloudinary.com/aenplcpl/image/upload/v1785917972/Screenshot_2026-08-05_011815_przm7l.png" alt="">
                                <div>
                                    <div class="cell-title">1-Day Horseback Wildlife Safari Experience</div>
                                    <div class="cell-sub">Horseback Wildlife Safari Experience

Durati...</div>
                                </div>
                            </div>
                        </td>
                        <td>Day Trip</td>
                        <td>Full day</td>
                        <td>$220</td>
                        <td>$0</td>
                        <td><span class="tag tag-green">Published</span></td>
                        <td>
                            <div class="row-actions">
                                <button onclick="editDestination(69)" title="Edit">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4Z"></path>
                                    </svg>
                                </button>
                                <button class="danger" onclick="confirmDeleteDest(69, '1-Day Horseback Wildlife Safari Experience')" title="Delete">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                                        <tr data-id="68" data-category="Multi-Day Safari" data-status="Published" data-name="6-day luxury tanzania family wildlife &amp; wilderness safari">
                        <td>
                            <div class="cell-main">
                                <img class="thumb" src="https://res.cloudinary.com/aenplcpl/image/upload/v1784634085/Screenshot_2026-07-21_044043_r6yjy0.png" alt="">
                                <div>
                                    <div class="cell-title">6-Day Luxury Tanzania Family Wildlife &amp; Wilderness Safari</div>
                                    <div class="cell-sub">6-Day Luxury Tanzania Family Wildlife &amp; Wilder...</div>
                                </div>
                            </div>
                        </td>
                        <td>Multi-Day Safari</td>
                        <td>6-Days</td>
                        <td>$3,450</td>
                        <td>$0</td>
                        <td><span class="tag tag-green">Published</span></td>
                        <td>
                            <div class="row-actions">
                                <button onclick="editDestination(68)" title="Edit">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4Z"></path>
                                    </svg>
                                </button>
                                <button class="danger" onclick="confirmDeleteDest(68, '6-Day Luxury Tanzania Family Wildlife &amp;amp; Wilderness Safari')" title="Delete">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                                        <tr data-id="67" data-category="Multi-Day Safari" data-status="Published" data-name="3-day zanzibar island highlights getaway">
                        <td>
                            <div class="cell-main">
                                <img class="thumb" src="https://res.cloudinary.com/aenplcpl/image/upload/v1783862480/Screenshot_2026-07-12_061918_k3irfv.png" alt="">
                                <div>
                                    <div class="cell-title">3-Day Zanzibar Island Highlights Getaway</div>
                                    <div class="cell-sub">3-Day Zanzibar Island Highlights Getaway.
Pri...</div>
                                </div>
                            </div>
                        </td>
                        <td>Multi-Day Safari</td>
                        <td>3-Days</td>
                        <td>$345</td>
                        <td>$0</td>
                        <td><span class="tag tag-green">Published</span></td>
                        <td>
                            <div class="row-actions">
                                <button onclick="editDestination(67)" title="Edit">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4Z"></path>
                                    </svg>
                                </button>
                                <button class="danger" onclick="confirmDeleteDest(67, '3-Day Zanzibar Island Highlights Getaway')" title="Delete">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                                        <tr data-id="66" data-category="Day Trip" data-status="Published" data-name="1-day mount meru forest &amp; arusha national park walking safari">
                        <td>
                            <div class="cell-main">
                                <img class="thumb" src="https://res.cloudinary.com/aenplcpl/image/upload/v1783779639/Screenshot_2026-07-11_071313-Picsart-AiImageEnhancer_bxpwlz.png" alt="">
                                <div>
                                    <div class="cell-title">1-Day Mount Meru Forest &amp; Arusha National Park Walking Safari</div>
                                    <div class="cell-sub">1-Day Mount Meru Forest &amp; Arusha National Park...</div>
                                </div>
                            </div>
                        </td>
                        <td>Day Trip</td>
                        <td>Full day</td>
                        <td>$175</td>
                        <td>$95</td>
                        <td><span class="tag tag-green">Published</span></td>
                        <td>
                            <div class="row-actions">
                                <button onclick="editDestination(66)" title="Edit">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4Z"></path>
                                    </svg>
                                </button>
                                <button class="danger" onclick="confirmDeleteDest(66, '1-Day Mount Meru Forest &amp;amp; Arusha National Park Walking Safari')" title="Delete">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                                        <tr data-id="65" data-category="Multi-Day Safari" data-status="Published" data-name="5 days timeless romance: the ultimate tanzanian honeymoon safari">
                        <td>
                            <div class="cell-main">
                                <img class="thumb" src="https://res.cloudinary.com/aenplcpl/image/upload/v1783762453/Unmatched_Views_from_the_Crater_Rim._Source_Cube_Nation_Tanzania_Ngorongoro_Weddings_Romantic_Escapes_-_Cube_Nation_faxwii.png" alt="">
                                <div>
                                    <div class="cell-title">5 Days Timeless Romance: The Ultimate Tanzanian Honeymoon Safari</div>
                                    <div class="cell-sub">5 Days Timeless Romance: The Ultimate Tanzania...</div>
                                </div>
                            </div>
                        </td>
                        <td>Multi-Day Safari</td>
                        <td>5-Days</td>
                        <td>$2,450</td>
                        <td>$0</td>
                        <td><span class="tag tag-green">Published</span></td>
                        <td>
                            <div class="row-actions">
                                <button onclick="editDestination(65)" title="Edit">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4Z"></path>
                                    </svg>
                                </button>
                                <button class="danger" onclick="confirmDeleteDest(65, '5 Days Timeless Romance: The Ultimate Tanzanian Honeymoon Safari')" title="Delete">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                                        <tr data-id="64" data-category="Multi-Day Safari" data-status="Published" data-name="4-day tanzania safari adventure">
                        <td>
                            <div class="cell-main">
                                <img class="thumb" src="https://res.cloudinary.com/aenplcpl/image/upload/v1783598502/Screenshot_2026-07-09_045347_tvm3r5.png" alt="">
                                <div>
                                    <div class="cell-title">4-Day Tanzania Safari Adventure</div>
                                    <div class="cell-sub">Best 4-Day Tanzania Safari Adventure
Your saf...</div>
                                </div>
                            </div>
                        </td>
                        <td>Multi-Day Safari</td>
                        <td>4-Days</td>
                        <td>$1,850</td>
                        <td>$0</td>
                        <td><span class="tag tag-green">Published</span></td>
                        <td>
                            <div class="row-actions">
                                <button onclick="editDestination(64)" title="Edit">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4Z"></path>
                                    </svg>
                                </button>
                                <button class="danger" onclick="confirmDeleteDest(64, '4-Day Tanzania Safari Adventure')" title="Delete">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                                        <tr data-id="63" data-category="Day Trip" data-status="Published" data-name="1-day vip serval wildlife sanctuary experience">
                        <td>
                            <div class="cell-main">
                                <img class="thumb" src="https://res.cloudinary.com/aenplcpl/image/upload/v1783352743/several_wildlife_teejza.jpg" alt="">
                                <div>
                                    <div class="cell-title">1-Day VIP Serval Wildlife Sanctuary Experience</div>
                                    <div class="cell-sub">1-Day VIP Serval Wildlife Sanctuary Experience...</div>
                                </div>
                            </div>
                        </td>
                        <td>Day Trip</td>
                        <td>Full day</td>
                        <td>$240</td>
                        <td>$120</td>
                        <td><span class="tag tag-green">Published</span></td>
                        <td>
                            <div class="row-actions">
                                <button onclick="editDestination(63)" title="Edit">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4Z"></path>
                                    </svg>
                                </button>
                                <button class="danger" onclick="confirmDeleteDest(63, '1-Day VIP Serval Wildlife Sanctuary Experience')" title="Delete">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                                        <tr data-id="62" data-category="Multi-Day Safari" data-status="Published" data-name="6 days ultimate wilderness camping safari">
                        <td>
                            <div class="cell-main">
                                <img class="thumb" src="https://res.cloudinary.com/aenplcpl/image/upload/v1783691928/Screenshot_2026-07-10_065323-Picsart-AiImageEnhancer_nexqyd.png" alt="">
                                <div>
                                    <div class="cell-title">6 Days Ultimate Wilderness Camping Safari</div>
                                    <div class="cell-sub">Explore Tanzania in 6 Days of Camping Safari...</div>
                                </div>
                            </div>
                        </td>
                        <td>Multi-Day Safari</td>
                        <td>6-Days</td>
                        <td>$1,800</td>
                        <td>$0</td>
                        <td><span class="tag tag-green">Published</span></td>
                        <td>
                            <div class="row-actions">
                                <button onclick="editDestination(62)" title="Edit">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4Z"></path>
                                    </svg>
                                </button>
                                <button class="danger" onclick="confirmDeleteDest(62, '6 Days Ultimate Wilderness Camping Safari')" title="Delete">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                                        <tr data-id="61" data-category="Multi-Day Safari" data-status="Published" data-name="explore 4 days ndutu the great migration season">
                        <td>
                            <div class="cell-main">
                                <img class="thumb" src="https://res.cloudinary.com/aenplcpl/image/upload/v1783692759/Screenshot_2026-07-10_070152_yhvxhc.png" alt="">
                                <div>
                                    <div class="cell-title">Explore 4 Days Ndutu the Great Migration Season</div>
                                    <div class="cell-sub">Detailed 4 Days Ndutu Migration Safari Adventu...</div>
                                </div>
                            </div>
                        </td>
                        <td>Multi-Day Safari</td>
                        <td>4-Days</td>
                        <td>$1,600</td>
                        <td>$0</td>
                        <td><span class="tag tag-green">Published</span></td>
                        <td>
                            <div class="row-actions">
                                <button onclick="editDestination(61)" title="Edit">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4Z"></path>
                                    </svg>
                                </button>
                                <button class="danger" onclick="confirmDeleteDest(61, 'Explore 4 Days Ndutu the Great Migration Season')" title="Delete">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                                        <tr data-id="60" data-category="Multi-Day Safari" data-status="Published" data-name="3-days premium camping safari adventures">
                        <td>
                            <div class="cell-main">
                                <img class="thumb" src="https://res.cloudinary.com/aenplcpl/image/upload/v1783690457/Screenshot_2026-07-10_063014_hyg7dk.png" alt="">
                                <div>
                                    <div class="cell-title">3-Days Premium Camping Safari Adventures</div>
                                    <div class="cell-sub">Detailed 3 Days Camping Safari:
 Duration: 3...</div>
                                </div>
                            </div>
                        </td>
                        <td>Multi-Day Safari</td>
                        <td>3-Days</td>
                        <td>$900</td>
                        <td>$0</td>
                        <td><span class="tag tag-green">Published</span></td>
                        <td>
                            <div class="row-actions">
                                <button onclick="editDestination(60)" title="Edit">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4Z"></path>
                                    </svg>
                                </button>
                                <button class="danger" onclick="confirmDeleteDest(60, '3-Days Premium Camping Safari Adventures')" title="Delete">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                                                        </tbody>
            </table>
        </div>
                <div class="table-pagination">
            <span class="disabled">Previous</span>
    
            
                                                        <span class="active" aria-current="page">1</span>
                                                                <a href="https://tanzaniadailytoursandsafari.com/live/destinations?page=2" aria-label="Go to page 2">2</a>
                                                                <a href="https://tanzaniadailytoursandsafari.com/live/destinations?page=3" aria-label="Go to page 3">3</a>
                                                                <a href="https://tanzaniadailytoursandsafari.com/live/destinations?page=4" aria-label="Go to page 4">4</a>
                                                                <a href="https://tanzaniadailytoursandsafari.com/live/destinations?page=5" aria-label="Go to page 5">5</a>
                                                                <a href="https://tanzaniadailytoursandsafari.com/live/destinations?page=6" aria-label="Go to page 6">6</a>
                                                                <a href="https://tanzaniadailytoursandsafari.com/live/destinations?page=7" aria-label="Go to page 7">7</a>
                                        
            <a href="https://tanzaniadailytoursandsafari.com/live/destinations?page=2" rel="next">Next</a>
    
        </div>
            </div>
</div>

<div class="modal-backdrop" id="destModalBackdrop">
    <div class="modal">
        <div class="modal-head">
            <h3 id="destModalTitle">Add destination</h3>
            <button class="modal-close" onclick="closeModal('destModalBackdrop')">✕</button>
        </div>
        <form id="destForm" onsubmit="handleSubmit(event)">
            <input type="hidden" name="_token" value="w3Vlfvw2Bh8VfqolGpMryy5mbgT3npZg5cr409mV" autocomplete="off">            <input type="hidden" id="destId" name="id">
            <input type="hidden" name="_method" value="" id="destMethod">
            <div class="modal-body">
                <div class="field">
                    <label>Destination name</label>
                    <input type="text" id="destName" name="name" placeholder="e.g. Materuni Waterfall & Coffee Tour" required>
                </div>
                <div class="form-row">
                    <div class="field">
                        <label>Category</label>
                        <select id="destCategory" name="category">
                            <option>Day Trip</option>
                            <option>Multi-Day Safari</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Status</label>
                        <select id="destStatus" name="status">
                            <option>Published</option>
                            <option>Draft</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="field">
                        <label>Duration</label>
                        <input type="text" id="destDuration" name="duration" placeholder="e.g. Full day or 3-5 Days">
                    </div>
                </div>
                <div class="form-row">
                    <div class="field">
                        <label>Adult Price (USD)</label>
                        <input type="number" id="destPriceAdult" name="price_adult" placeholder="80">
                    </div>
                    <div class="field">
                        <label>Child Price (USD)</label>
                        <input type="number" id="destPriceChild" name="price_child" placeholder="40">
                    </div>
                </div>
                <div class="field">
                    <label>Cover image URL</label>
                    <input type="text" id="destImage" name="image" placeholder="https://…/tour-materuni.jpg">
                </div>
                <div class="field">
                    <label>Short description</label>
                    <textarea id="destDesc" name="desc" rows="3" placeholder="Hike through lush forests to a stunning waterfall…"></textarea>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" onclick="closeModal('destModalBackdrop')">Cancel</button>
                <button type="submit" class="btn btn-primary" id="destSubmitBtn">Save destination</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-backdrop" id="confirmModalBackdrop">
    <div class="modal" style="max-width:400px;">
        <div class="modal-head">
            <h3>Delete item?</h3>
            <button class="modal-close" onclick="closeModal('confirmModalBackdrop')">✕</button>
        </div>
        <div class="modal-body">
            <p style="font-size:14px;color:var(--ink-soft);line-height:1.6;" id="confirmText">This action cannot be undone.</p>
        </div>
        <div class="modal-foot">
            <button class="btn btn-ghost" onclick="closeModal('confirmModalBackdrop')">Cancel</button>
            <button class="btn btn-danger" id="confirmDeleteBtn" onclick="executeDelete()">Delete</button>
        </div>
    </div>
</div>

<script>
let destinationsData = [{"id":69,"name":"1-Day Horseback Wildlife Safari Experience","slug":"1-day-horseback-wildlife-safari-experience","category":"Day Trip","duration":"Full day","price":"220.00","price_adult":"220.00","price_child":"0.00","status":"Published","image":"https:\/\/res.cloudinary.com\/aenplcpl\/image\/upload\/v1785917972\/Screenshot_2026-08-05_011815_przm7l.png","desc":"Horseback Wildlife Safari Experience\r\n\r\nDuration: Full Day (Approx. 7\u20138 Hours)\r\nLocation: Dolly Estate, Usa River (Near Arusha \u0026 Kilimanjaro)\r\nSkill Level: Beginners to Experienced Riders\r\n\r\nTOUR OVERVIEW\r\nRide alongside wildlife in the shadow of Mount Kilimanjaro\r\nEscape the traditional safari vehicle and experience the Tanzanian wilderness from an intimate, exhilarating perspective. Our 1-Day Horseback Wildlife Safari takes you through the private 4,000-acre Dolly Estate in Usa River\u2014a serene wildlife sanctuary nestled between Mount Meru and Mount Kilimanjaro.\r\n\r\nRiding on horseback allows you to approach wild animals quietly without the noise or disruption of an engine. Glide across open plains and acacia woodlands as zebras, giraffes, wildebeests, and gazelles graze peacefully alongside your horse. With well-trained horses paired to your riding ability and expert equestrian guides leading the way, this is an unforgettable day trip for solo travelers, couples, and families alike.\r\n\r\nTOUR HIGHLIGHTS\r\nUp-Close Wildlife Sightings: Get remarkably close to giraffes, zebras, elands, impalas, and rich birdlife.\r\n\r\nIconic Mountain Views: Enjoy spectacular backdrops of Mount Meru and Mount Kilimanjaro on clear days.\r\n\r\nTailored Riding Experience: Gentle, well-mannered horses matched to your experience level\u2014from slow walking trots for beginners to open canters for experienced riders.\r\n\r\nGourmet Bush Lunch: Relax with a delicious 2-course hot lunch and cold drinks at the polo club house or under shaded acacias.\r\n\r\nHassle-Free Day Trip: Ideal activity when staying in Arusha, Moshi, or transit before\/after a Kilimanjaro climb or safari.\r\n\r\nDETAILED ITINERARY SCHEDULE\r\n1.08:00 AM \u2014 Departure from Arusha or Moshi:Hotel Pick-Up \u0026 Scenic Transfer.Your private driver-guide will meet you at your hotel or lodge in Arusha or Moshi. Enjoy a smooth, scenic drive toward Usa River, passing coffee plantations and local villages before entering the private Dolly Estate grounds.\r\n\r\n2.09:15 AM \u2014 Arrival \u0026 Equipment Fitting:Safety Briefing \u0026 Horse Pairing.Arrive at the stables for a warm welcome by our professional equestrian team. You will be fitted with safety gear (helmets provided) and receive a brief safety orientation. Beginners are given basic riding instruction, while experienced riders are matched with energetic, high-fit mounts.\r\n\r\n3.10:00 AM \u2014 Morning Wildlife Ride:2-Hour Guided Horse Riding Safari.Mount up and ride out across open acacia savannah and shaded woodland trails. Follow your lead guide as you track herds of zebras, wildebeests, and majestic giraffes roaming freely across the estate plains.\r\n\r\n4.12:30 PM \u2014 Hot Lunch \u0026 Relaxation:Delicious Bush Dining.Dismount at the estate lounge pavilion. Savor a freshly prepared two-course lunch accompanied by chilled drinks, tea, and local coffee while enjoying views of the estate paddocks and surrounding countryside.\r\n\r\n5.02:30 PM \u2014 Estate Walk \u0026 Relaxation:Optional Afternoon Trail \u0026 Photos.Enjoy an optional short walk around the estate grounds, photograph the horses, or unwind in the shade overlooking Mount Meru before preparing for departure.\r\n\r\n6.04:00 PM \u2014 Transfer Back to Hotel:Comfortable Return Journey.Bid farewell to the horses and riding team. Board your vehicle for a comfortable return transfer, arriving at your hotel in time for evening drinks or your next travel connection.\r\n\r\nRecommended Packing Checklist:\r\nClothing: Long trousers or riding breeches (jeans work well), closed-toe shoes or boots with a small heel.\r\nSun Protection: Sunscreen, sunglasses, and a hat for post-ride relaxation.\r\nCamera Gear: Camera or smartphone with a neck or wrist strap for safe riding photos.\r\n\r\nINCLUSIONS \u0026 EXCLUSIONS:\r\nWhat\u0027s Included:\r\nProfessional Equestrian Guide\r\nExperienced horse \u0026 safety helmet\r\n2 hours of guided wildlife riding\r\nHot 2-course lunch \u0026 soft drinks\r\nEstate access \u0026 conservation fees\r\n\r\nWhat\u0027s Excluded:\r\nInternational or domestic flights\r\nRound-trip hotel transfer (unless added as package)\r\nPersonal riding boots\/gloves\r\nAlcoholic beverages \u0026 tips for guides\r\nTravel \/ Medical Insurance","long_description":null,"includes":null,"meta_title":"1-Day Horseback Wildlife Safari Experience - Day Trip | Tanzania Daily Tours \u0026 Safari","meta_description":"Book 1-Day Horseback Wildlife Safari Experience with expert local guides. Full day Day Trip experience. Best prices starting from $220. Experience authentic Tan...","meta_keywords":"1-Day Horseback Wildlife Safari Experience, Tanzania 1-Day Horseback Wildlife Safari Experience, 1-Day Horseback Wildlife Safari Experience safari, Day Trip, Tanzania Day Trip, Day Trip Tanzania, Tanzania safari, Tanzania tour, wildlife safari, safari packages, Tanzania tours, Serengeti safari, Ngorongoro safari, Kilimanjaro tours, Tanzania national parks","created_at":"2026-08-05T08:36:10.000000Z","updated_at":"2026-08-05T08:36:10.000000Z"},{"id":68,"name":"6-Day Luxury Tanzania Family Wildlife \u0026 Wilderness Safari","slug":"6-day-luxury-tanzania-family-wildlife-wilderness-safari","category":"Multi-Day Safari","duration":"6-Days","price":"3450.00","price_adult":"3450.00","price_child":"0.00","status":"Published","image":"https:\/\/res.cloudinary.com\/aenplcpl\/image\/upload\/v1784634085\/Screenshot_2026-07-21_044043_r6yjy0.png","desc":"6-Day Luxury Tanzania Family Wildlife \u0026 Wilderness Safari\r\nPricing: From $3,450 Per Adult (Child discounts apply) | Duration: 6 Days \/ 5 Nights | Destinations: Tarangire, Serengeti \u0026 Ngorongoro Crater | Travel Style: Private Luxury Family Safari\r\n\r\nTOUR OVERVIEW:\r\nUnforgettable Family Bonding in Africa\u2019s Crown Jewels\r\nTreat your family to an extraordinary African adventure where world-class luxury meets the wild heart of Tanzania. Designed specifically with family comfort and pacing in mind, this 6-day private safari balances thrilling Big Five wildlife encounters with relaxed, stress-free luxury stayovers featuring family suites, swimming pools, and tailored junior ranger activities for kids.\r\n\r\nYour private family journey takes you across Tanzania\u0027s famed Northern Circuit: marvel at giant elephant herds framed by ancient baobabs in Tarangire National Park, sweep across the legendary predator-rich plains of the Serengeti, and descend into the breathtaking, prehistoric Ngorongoro Crater. Driven by a dedicated, child-friendly professional guide, your family will enjoy the utmost safety, flexibility, and comfort every step of the way.\r\n\r\nWhy Choose the 6-Day Luxury Tanzania Family Wildlife \u0026 Wilderness Safari?\r\nThe 6-Day Luxury Tanzania Family Wildlife \u0026 Wilderness Safari is specifically crafted to deliver an unforgettable, seamless, and safe African adventure for travelers of all ages. Combining world-class wildlife destinations with exclusive luxury lodges, this itinerary balances exhilarating game drives, child-friendly wilderness experiences, and supreme comfort.\r\n\r\nIconic Northern Circuit Highlights: Explore Tarangire National Park, the legendary Serengeti National Park, and the breathtaking Ngorongoro Crater\u2014home to the Big Five and the Great Migration.\r\n\r\nFamily-Centric Comfort \u0026 Safety: Travel in a private, custom-built 4x4 Land Cruiser equipped with pop-up roofs, charging ports, Wi-Fi, and child safety features. Enjoy relaxed daily schedules tailored to your family\u0027s pace.\r\n\r\nHandpicked Luxury Accommodations: Stay in five-star luxury lodges and tented camps offering interconnecting family suites, swimming pools, kid-friendly dining, and personalized hospitality.\r\n\r\nImmersive Educational Experiences: From guided bush walks and Maasai cultural visits to junior ranger activities, your children gain deep insights into wildlife conservation and African traditions.\r\n\r\nTOUR ITINERARY:\r\n1.Day 1: Arusha to Tarangire National Park \u2014 Land of the Giants:Elephants \u0026 Baobabs.Your private driver-guide greets your family in Arusha for a comfortable drive to Tarangire National Park. Known for its massive elephant herds and iconic baobab trees, Tarangire offers instant wildlife excitement. Enjoy a leisurely afternoon game drive spotting lions, giraffes, and zebras before checking into your luxury lodge for a refreshing swim and dinner.\r\n\r\nACCOMMODATION: Tarangire Treetops by Elewana\r\n\r\n2.Day 2: Tarangire to Central Serengeti National Park:Journey to the Endless Plains.After a relaxed breakfast, journey through the scenic highlands toward the world-famous Serengeti. As you enter the park gates, the vast golden savannah unfolds. Arrive at your luxury tented camp in time for lunch. Spend the late afternoon tracking leopards and cheetahs on your first Serengeti game drive, returning to camp for fireside storytelling under the African stars.\r\n\r\nACCOMMODATION: Four Seasons Safari Lodge Serengeti\r\n\r\n3.Day 3: Full-Day Family Safari in Serengeti National Park:Big Cats \u0026 Bush Magic.Dedicate the entire day to exploring the Serengeti\u0027s wildlife-rich Seronera Valley. Watch lion prides basking on granite kopjes and spot hippos splashing in the rivers. Your guide will set up a private, luxury bush lunch under an acacia tree. (Optional highlight: Book an early morning Hot Air Balloon Safari over the Serengeti plains).\r\n\r\nACCOMMODATION: Four Seasons Safari Lodge Serengeti\r\n\r\n4.Day 4: Serengeti to Ngorongoro Conservation Area:Highland Rim Views.Enjoy a final morning game drive in the Serengeti before journeying toward the Ngorongoro Conservation Area. Stop along the way for an interactive, child-friendly cultural visit to a local Maasai village, where children can learn traditional dances and fire-making. Check into your luxury lodge perched right on the Ngorongoro Crater rim with spectacular panoramic views.\r\n\r\nACCOMMODATION: The Ngorongoro Crater Lodge \u0026Beyonds\r\n\r\n5.Day 5: Ngorongoro Crater Floor Safari \u2014 The Big Five Sanctuary:World Natural Wonder.Descend 600 meters into the magnificent Ngorongoro Crater floor for an action-packed morning safari. This natural haven offers your best chance to spot the endangered Black Rhino alongside thousands of wildebeest, flamingoes, and hyenas. Enjoy a picturesque picnic lunch beside a hippo pool before ascending back to your lodge for relaxation.\r\n\r\nACCOMMODATION: Gibb\u2019s Farm Karatu\r\n\r\n6.Day 6: Karatu \/ Lake Manyara Views \u0026 Return to Arusha:Souvenirs \u0026 Farewell.After a peaceful breakfast, take a leisurely drive back toward Arusha. Stop at an authentic cultural art centre for souvenir shopping and local crafts. Enjoy a farewell family lunch in town before your driver transfers you directly to Kilimanjaro International Airport (JRO) or Arusha Airport for your flight home or onward connection to Zanzibar.\r\n\r\nPlan Your Tanzania Family Wildlife \u0026 Wilderness Safari.\r\n\r\nPlanning your dream family safari in Tanzania is simple and stress-free. Our team of expert safari specialists handles every detail\u2014from custom route planning and internal flights to dietary arrangements and lodge bookings.\r\n\r\nCustomization: Tell us your travel dates, family size, children\u0027s ages, and preferred travel style.\r\n\r\nSeamless Logistics: We handle all airport transfers, private transport, park entry permits, and luxury lodge reservations.\r\n\r\nExpert Guidance: You will be accompanied by a dedicated, bilingual driver-guide specializing in family safaris and child engagement.\r\n\r\nReady to design your private family adventure?\r\n\r\nContact our safari planning team directly at  info.tanzaniadailytours@gmail.com  to receive a tailored itinerary and complimentary quote.\r\n\r\nWHY BOOK WITH US\r\n\r\nPrivate 4x4 Vehicles:\r\nWhat We Offer; Customized, high-top Land Cruisers reserved exclusively for your family\r\nFamily Benefit; Total privacy, flexible pace, and comfortable seating with child seats available\r\n\r\nChild-Specialized Guides:\r\nWhat We Offer; Native, English-speaking professional guides trained in family safety and kids\u0027 engagement\r\n Family Benefit; Engaging, educational, and fun wildlife insights tailored for younger travelers\r\n\r\nHandpicked Accommodations:\r\nWhat We Offer; Vetted luxury lodges with family suites, swimming pools, and fenced grounds\r\n Family Benefit; Safety, maximum comfort, high-end amenities, and tailored kids\u0027 meal menus\r\n\r\n24\/7 On-Ground Support:\r\nWhat We Offer; Round-the-clock dedicated concierge and emergency assistance\r\n Family Benefit; Total peace of mind throughout your journey across Tanzania\r\n\r\nTailor-Made Flexibility:\r\nWhat We Offer; Flexible daily itineraries adaptable on the fly to suit family energy levels\r\n Family Benefit; Zero rush or rigid schedules\u2014enjoy safaris at your own comfortable pace\r\n\r\nINCLUDED \u0026 EXCLUDED:\r\nWhat\u2019s Included:\r\nPrivate Transport: Exclusive use of a customized 4x4 Toyota Land Cruiser with pop-up roof, unlimited mileage, fridge, Wi-Fi, and charging ports.\r\n\r\nExpert Guide: Professional, licensed English-speaking native driver-guide.\r\n\r\nLuxury Accommodations: 5 nights in premier luxury lodges\/tented camps as specified.\r\n\r\nAll Meals: Full board meal plan (Breakfast, Lunch, Gourmet Dinner) including bottled mineral water during game drives.\r\n\r\nPark \u0026 Conservation Fees: All entry fees, vehicle fees, and crater service fees for Tarangire, Serengeti, and Ngorongoro.\r\n\r\nAirport Transfers: Private transfers from\/to Kilimanjaro International Airport (JRO) or Arusha Airport.\r\n\r\nCultural Experiences: Guided Maasai village visit and Mto wa Mbu cultural tour.\r\n\r\nAMREF Flying Doctors Insurance: Emergency air evacuation coverage throughout the safari.\r\n\r\nWhat\u2019s Excluded:\r\nInternational Flights: Overseas airfare to and from Tanzania.\r\n\r\nVisas: Tanzania Tourist Visa ($50 - $100 per person depending on nationality).\r\n\r\nOptional Activities: Serengeti Hot Air Balloon Safari (approx. $550\u2013$600 per person).\r\n\r\nAlcoholic \u0026 Premium Beverages: Premium wines, spirits, and champagne (unless included in specific luxury lodge packages).\r\n\r\nGratuities: Tips for driver-guides, lodge staff, and local trackers.\r\n\r\nPersonal Expenses: Laundry services, phone calls, souvenirs, and travel insurance.\r\n\r\nLUXURY FAMILY SAFARI FAQs\r\nFrequently Asked Questions\r\n1. Is a 6-day luxury safari in Tanzania safe for young children?\r\nYes, absolutely. Tanzania is one of Africa\u2019s safest safari destinations. Private safaris ensure your family travels in a secure, enclosed 4x4 vehicle with an experienced guide. Accommodations selected for this package feature high safety standards, fenced parameters, and child-friendly amenities.\r\n\r\n2. What is the best time of year for a family luxury safari in Tanzania?\r\nTanzania offers outstanding wildlife viewing year-round. June to October is the dry season, providing clear weather and peak wildlife viewing around water sources. December to March is warm, excellent for bird watching, and ideal for witnessing the wildebeest calving season in the southern Serengeti.\r\n\r\n3. How long are the daily drives between parks?\r\nDrive times average 2 to 4 hours between major parks. The routes pass through scenic landscapes, villages, and wildlife areas, offering frequent stops for stretch breaks, picnics, and photography to keep children comfortable.\r\n\r\n4. Can dietary requirements (vegan, gluten-free, kids\u0027 meals) be accommodated?\r\nYes. All luxury lodges and camps cater seamlessly to dietary requests, including vegetarian, vegan, gluten-free, nut allergies, and child-friendly preferences. Please notify us during booking so menus are pre-arranged.\r\n\r\n5. What style of vehicle will we travel in?\r\nYou will travel in a private, heavy-duty 4x4 Toyota Land Cruiser equipped with pop-up roofs for 360-degree viewing, air conditioning, power outlets for charging devices, a mini-refrigerator with cool drinks, and child safety booster seats upon request.\r\n\r\n6. Are there age limits for kids on a Tanzania safari?\r\nThere are no age restrictions for private safaris. However, we recommend children be at least 4 or 5 years old to fully enjoy game drives. Walking safaris or balloon rides may have specific age minimums set by park authorities (typically 12 years and 7 years respectively).\r\n\r\n7. What vaccinations or medical precautions are required for Tanzania?\r\nTravelers should consult a travel clinic before departure. Routine vaccines, Hepatitis A, Typhoid, and Malaria prophylaxis are recommended. A Yellow Fever vaccination certificate is required if arriving from or transiting through endemic countries.\r\n\r\n8. How do we book the 6-Day Luxury Tanzania Family Wildlife \u0026 Wilderness Safari?\r\nSimply send an email to info.tanzaniadailytours@gmail.com with your preferred travel dates, family size, and ages of children. Our team will send you a customized proposal, lodge availability, and booking instructions.\r\n\r\n9. What should my family pack for a luxury safari?\r\nPack light, breathable, neutral-colored clothing (khaki, beige, green), comfortable walking shoes, a warm jacket or fleece for cool mornings\/evenings, wide-brim hats, sunglasses, sunscreen, insect repellent, personal medications, and cameras\/binoculars.\r\n\r\n10. Can we add extra days or a Zanzibar beach extension to this itinerary?\r\nYes! All our itineraries are 100% customizable. Many families extend their 6-day safari with a 3 to 5-day tropical beach relaxation package on the spice island of Zanzibar, connected by a direct short flight from Arusha or Seronera.","long_description":null,"includes":null,"meta_title":"6-Day Luxury Tanzania Family Wildlife \u0026 Wilderness Safari - Multi-Day Safari | Tanzania Daily Tours \u0026 Safari","meta_description":"Book 6-Day Luxury Tanzania Family Wildlife \u0026 Wilderness Safari with expert local guides. 6-Days Multi-Day Safari experience. Best prices starting from $3450. Ex...","meta_keywords":"6-Day Luxury Tanzania Family Wildlife \u0026 Wilderness Safari, Tanzania 6-Day Luxury Tanzania Family Wildlife \u0026 Wilderness Safari, 6-Day Luxury Tanzania Family Wildlife \u0026 Wilderness Safari safari, Multi-Day Safari, Tanzania Multi-Day Safari, Multi-Day Safari Tanzania, Tanzania safari, Tanzania tour, wildlife safari, safari packages, Tanzania tours, Serengeti safari, Ngorongoro safari, Kilimanjaro tours, Tanzania national parks","created_at":"2026-07-21T11:49:31.000000Z","updated_at":"2026-08-12T08:15:42.000000Z"},{"id":67,"name":"3-Day Zanzibar Island Highlights Getaway","slug":"3-day-zanzibar-island-highlights-getaway","category":"Multi-Day Safari","duration":"3-Days","price":"345.00","price_adult":"345.00","price_child":"0.00","status":"Published","image":"https:\/\/res.cloudinary.com\/aenplcpl\/image\/upload\/v1783862480\/Screenshot_2026-07-12_061918_k3irfv.png","desc":"3-Day Zanzibar Island Highlights Getaway.\r\nPricing: From $345 Per Person | Duration: 3 Days \/ 2 Nights | Location: Zanzibar Island (Unguja | Tour Type: Culture, History \u0026 Beach\r\n\r\nTOUR OVERVIEW\r\nSwahili Culture \u0026 Pristine Paradise: The Ultimate Short Escape\r\nImmerse yourself in the exotic allure of the Spice Island with this perfectly crafted 3-day tropical itinerary. Designed for travelers who want to experience the absolute best of Zanzibar in a limited timeframe, this getaway seamlessly blends the rich, ancient history of the sultanates with the breathtaking beauty of the Indian Ocean.\r\nYou will begin your journey winding through the labyrinthine, coral-stone alleys of historic Stone Town, where the scent of cloves and coffee fills the air. Explore vibrant spice plantations to touch, smell, and taste exotic tropical treasures right from the trees. Finally, escape to the far north to unwind on the powder-white sands of world-renowned Nungwi Beach, where turquoise waters and unforgettable golden sunsets await you. It\u2019s the perfect short holiday, post-safari wind-down, or romantic weekend retreat.\r\n\r\nTOUR ITINERARY:\r\n1.Day 1: Arrival, Stone Town Walking Tour \u0026 Forodhani Night Market:Arrival \u0026 Heritage.Arrive at Zanzibar International Airport or the ferry terminal, where your private driver will greet you and transfer you to your charming hotel in Stone Town. In the afternoon, embark on a guided walking tour of this UNESCO World Heritage site. Visit the historic Slave Market site, the Old Fort, and the Sultan\u0027s Palace. As dusk falls, head to Forodhani Gardens to experience the famous lively night market, sampling local delicacies like Zanzibar pizza and fresh sugarcane juice.\r\n\r\n2.Day 2: Authentic Spice Tour \u0026 Transfer to Nungwi Tropical Beach:Spices \u0026 Sunsets.After breakfast, check out and drive into the lush countryside for a sensory Spice Farm Tour. Learn how vanilla, nutmeg, cardamom, and cinnamon are grown, and taste fresh seasonal fruits. Afterward, travel north to the iconic Nungwi Beach. Check into your beachfront resort and spend the afternoon swimming in the tide-free turquoise waters or relaxing under a palm tree as you watch traditional dhow cruise boats sail past the sunset.\r\n\r\n3.Day 3: Beach Morning \u0026 Private Departure Transfer:Paradise \u0026 Departure.Enjoy a leisurely breakfast overlooking the ocean. Spend your final morning soaking up the sun, walking along the white shoreline, or doing some last-minute souvenir shopping for local textiles and artwork. In the afternoon, your private driver will pick you up from the resort and transfer you safely back to the airport or ferry terminal for your onward journey home.\r\n\r\nINCLUDED \u0026 EXCLUDED:\r\nWhat\u2019s Included:\r\n(Accommodation): 2 nights in comfortable, hand-picked mid-range hotels (1 night Stone Town, 1 night Nungwi Beach) on a Bed \u0026 Breakfast basis.\r\n\r\n(Transfers): All private, air-conditioned road transfers as specified in the itinerary (Airport\/Ferry pickups and drop-offs).\r\n\r\n(Activity Fees): Fully guided Stone Town walking tour and the rural Spice Farm entrance fees.\r\n\r\nProfessional Guide: Services of a licensed, knowledgeable local guide fluent in English\/Swahili.\r\n\r\nWhat\u2019s Excluded:\r\n(Flights\/Ferry): Domestic or international flights and high-speed ferry tickets to\/from Zanzibar.\r\n\r\n(Meals): Lunches and dinners (except breakfast at the hotels) to give clients the freedom to explore local dining options.\r\n\r\n(Optional Activities): Ocean snorkeling trips, diving, or dhow sunset cruise supplements.\r\n\r\n(Personal Expenses): Tips\/gratuities for drivers and guides, laundry, and alcoholic drinks.\r\n\r\nZANZIBAR 3-DAY GETAWAY FAQs\r\nFrequently Asked Questions\r\nQ: Is 3 days enough time to experience Zanzibar properly?\r\n\r\nA: While Zanzibar has enough attractions to fill a week, 3 days is the perfect amount of time for a highlight or snapshot trip. This specific itinerary is strategically optimized to ensure you experience the core cultural heart (Stone Town), the natural heritage (Spice Tour), and the world-class beaches (Nungwi) without feeling rushed or spending too much time on the road.\r\n\r\nQ: Do I need to worry about ocean tides when swimming at Nungwi Beach?\r\n\r\nA: No! One of the main reasons we choose Nungwi Beach for short trips is because it is located on the northern tip of the island, meaning it is one of the very few beaches in Zanzibar that is virtually unaffected by the dramatic low tides. You can swim, float, and enjoy the pristine turquoise water at any time of the day.\r\n\r\nQ: What is the dress code for walking around Stone Town?\r\n\r\nA: Zanzibar is a predominantly Muslim and culturally conservative society. While swimwear and shorts are perfectly acceptable at your beach resort in Nungwi, we highly recommend dressing modestly when walking through the public streets of Stone Town. Both men and women should keep their shoulders and knees covered (e.g., lightweight trousers, maxi dresses, or t-shirts) as a sign of respect for the local community.","long_description":null,"includes":null,"meta_title":null,"meta_description":null,"meta_keywords":null,"created_at":"2026-07-12T13:33:51.000000Z","updated_at":"2026-07-12T13:34:01.000000Z"},{"id":66,"name":"1-Day Mount Meru Forest \u0026 Arusha National Park Walking Safari","slug":"1-day-mount-meru-forest-arusha-national-park-walking-safari","category":"Day Trip","duration":"Full day","price":"175.00","price_adult":"175.00","price_child":"95.00","status":"Published","image":"https:\/\/res.cloudinary.com\/aenplcpl\/image\/upload\/v1783779639\/Screenshot_2026-07-11_071313-Picsart-AiImageEnhancer_bxpwlz.png","desc":"1-Day Mount Meru Forest \u0026 Arusha National Park Walking Safari\r\nPricing: From $165 Per Person | Duration: 1 Day (08:00 AM \u2013 05:00 PM) | Pick-up\/Drop-off: Arusha or Moshi | Activity Level: Easy to Moderate\r\n\r\nTOUR OVERVIEW:\r\nWalk Among Giants: The Ultimate Foot Safari \u0026 Rainforest Adventure\r\nTrade the safari vehicle for your own two feet and experience the raw thrill of walking alongside Africa\u2019s iconic wildlife. Located just a short drive from Arusha and Moshi, the Mount Meru Forest \u0026 Arusha National Park Day Trip is Tanzania\u2019s best-kept secret for active travelers, nature lovers, and birdwatchers.\r\n\r\nLed by an expert, armed National Park Ranger, you will step directly onto the volcanic slopes of Mount Kili\u2019s dramatic sister peak, Mount Meru. Walk through lush montane forests, hidden waterfalls, and open savannah plains where you can stand just meters away from wild giraffes, zebras, and buffaloes. Look up into the ancient canopy to spot the magnificent, rare black-and-white colobus monkeys leaping through the trees. Combining a thrilling walking safari, a classic game drive, and an optional tranquil canoe safari on the scenic Momella Lakes, this day trip offers an intimate, crowd-free taste of wild Africa.\r\n\r\nTOUR ITINERARY:\r\n1.08:00 AM \u2013 Private Pickup \u0026 Scenic Transfer:Morning Departure.Your private driver-guide will pick you up from your hotel in Arusha or Moshi. Enjoy a brief, comfortable drive to the Arusha National Park gate. Keep your camera ready as you pass through the entrance\u2014baboons, giraffes, and warthogs often line the roadside to welcome visitors.\r\n\r\n2.09:30 AM \u2013 Guided Forest Walking Safari \u0026 Waterfall Hike:On-Foot Adventure.Meet your dedicated, armed TANAPA ranger at the Momella Gate. Step into the wild for a 2-to-3 hour gentle walking safari. Walk through the scenic Tululusia Hill and proceed to a stunning hidden waterfall. Stand out in the open plains to observe giraffes, buffaloes, and zebras grazing completely undisturbed by vehicle engines.\r\n\r\n3.12:30 PM \u2013 Picnic Lunch at the Momella Lakes Viewpoint:Panoramic Rest.Head to a beautiful, elevated picnic site overlooking the pristine Momella Lakes. Savor a freshly prepared gourmet lunch box while enjoying panoramic views of the water, which is frequently painted pink by thousands of flamingos, all with the dramatic ash cone of Mount Meru towering behind you.\r\n\r\n4.02:00 PM \u2013 Optional Lake Canoeing \u0026 Ngurdoto Crater Drive:Water Safari or Game Drive.Choose your afternoon adventure! You can embark on a peaceful 2-hour canoe safari on the calm waters of small Momella Lake to view hippos and waterbirds up close. Alternatively, take a classic game drive through the lush forest toward the dramatic Ngurdoto Crater, often called \u0022Little Ngorongoro,\u0022 to spot elephants and leopards.\r\n\r\n5.04:00 PM \u2013 Return Journey \u0026 Hotel Drop-off:Heading Home.After a full day of active exploration and wildlife photography, board your private transport for a relaxing ride back to town, arriving at your hotel in Arusha or Moshi by late afternoon in time for dinner.\r\n\r\nINCLUDED \u0026 EXCLUDED:\r\nWhat\u2019s Included:\r\nOfficial Park Entry Fees: 100% of all Arusha National Park conservation and entry permits.\r\n\r\nArmed Ranger Fee: Compulsory services of a certified, armed National Park Ranger for the walking safari.\r\n\r\nPrivate 4x4 Vehicle: Exclusive round-trip transport from Arusha or Moshi in a comfortable safari vehicle.\r\n\r\nProfessional Driver-Guide: A knowledgeable, fluent English-speaking local guide.\r\n\r\nGourmet Lunch Box: A freshly packed lunch with vegetarian, vegan, and allergy-friendly options available on request.\r\n\r\nComplimentary Drinks: Unlimited bottled mineral water inside the vehicle throughout the day.\r\n\r\nWhat\u2019s Excluded:\r\nOptional Canoeing Activity: Lake canoeing safari supplement fee (approx. $40\u2013$50 USD per person).\r\n\r\nGratuities: Optional tips for your private driver-guide and the armed park ranger.\r\n\r\nPersonal Gear: Hiking shoes, sun hats, and insect repellent.\r\n\r\nTravel Insurance: Personal medical and trip cancellation insurance.\r\n\r\nMERU FOREST DAY TRIP FAQs\r\nFrequently Asked Questions\r\nQ: Is it safe to walk near wild animals like buffaloes and giraffes?\r\n\r\nA: Yes, it is very safe. Your walking safari is strictly conducted by a highly trained, armed National Park Ranger who understands animal behavior perfectly. The ranger maintains a safe, respectful distance from the wildlife and knows the safest trails. Buffaloes and giraffes in this area are well-accustomed to human presence on foot, making it a thrilling yet completely secure experience.\r\n\r\nQ: How physically demanding is the walking safari?\r\n\r\nA: The walk is gentle to moderate and can easily be customized to your fitness level. The terrain consists of established dirt paths and open grasslands with a few light inclines near the waterfall. A basic level of physical fitness is all that is required, making this trip highly suitable for families, senior travelers, and casual hikers alike.\r\n\r\nQ: What should I wear for this specific day trip?\r\n\r\nA: Because you will be walking on foot through the forest, we strongly recommend wearing sturdy walking shoes, sneakers, or hiking boots with good grip. Wear comfortable, lightweight clothing in neutral colors (avoid bright blue or black, which attracts insects). Bringing a light rain jacket or fleece is smart, as the Meru montane forest can be misty and cool in the mornings.","long_description":null,"includes":null,"meta_title":null,"meta_description":null,"meta_keywords":null,"created_at":"2026-07-11T14:26:57.000000Z","updated_at":"2026-07-11T14:26:57.000000Z"},{"id":65,"name":"5 Days Timeless Romance: The Ultimate Tanzanian Honeymoon Safari","slug":"5-days-timeless-romance-the-ultimate-tanzanian-honeymoon-safari","category":"Multi-Day Safari","duration":"5-Days","price":"2450.00","price_adult":"2450.00","price_child":"0.00","status":"Published","image":"https:\/\/res.cloudinary.com\/aenplcpl\/image\/upload\/v1783762453\/Unmatched_Views_from_the_Crater_Rim._Source_Cube_Nation_Tanzania_Ngorongoro_Weddings_Romantic_Escapes_-_Cube_Nation_faxwii.png","desc":"5 Days Timeless Romance: The Ultimate Tanzanian Honeymoon Safari\r\nPricing: From $2,450 Per Person | Type: 100% Private Honeymoon Escape | Destinations: Tarangire, Lake Manyara \u0026 Ngorongoro Crater Rim\r\n\r\nTOUR OVERVIEW\r\nCelebrate Your Forever in Africa\u2019s Wildest Paradise\r\nThere is no love story quite like the one written under the vast African sky. Our 5-Day Romantic Tanzania Honeymoon Safari is an intimately tailored, ultra-private journey designed exclusively for newlyweds who want to celebrate their union surrounded by jaw-dropping wildlife, raw natural beauty, and uncompromised luxury.\r\n\r\nLeave the crowded tour buses behind. From the moment you arrive, you will be treated to a completely private, chauffeured 4x4 Safari Land Cruiser, ensuring your journey is as intimate as it is adventurous. Wake up in floating luxury treehouses, sip champagne during private sunset viewings overlooking elephant herds, and dine by candlelight beneath the ancient stars of the savannah. We handle every romantic detail\u2014from complimentary honeymoon wine and rose-petal welcomes to private bush dinners\u2014allowing you both to focus entirely on each other and the magic of East Africa.\r\n\r\nTOUR ITINERARY:\r\n1.Day 1: Arusha to Tarangire - Love at First Sight:VIP Welcome.Your romantic escape begins with a private morning pickup from Arusha. Drive into Tarangire National Park, celebrated for its legendary giant elephants and iconic baobab trees. After an exhilarating afternoon game drive tracking lions, arrive at your luxury tented lodge. You\u2019ll be welcomed with a chilled bottle of sparkling wine, followed by a private, candlelit multi-course dinner under the stars.\r\n\r\n2.Day 2: Tarangire Private Sundowners \u0026 Bush Magic:Savannah Romance.Spend a full day exploring the hidden corners of Tarangire with your private guide. In the late afternoon, we arrange a surprise: a private safari sundowner setup. Sip premium South African wine and enjoy artisanal cheeses on a scenic ridge as the sun paints the sky in shades of gold and violet, surrounded only by the quiet sounds of the wild.\r\n\r\n3.Day 3: Lake Manyara Canopy Exploration \u0026 Luxury Treehouse Stay:Treehouse Luxury.Travel to Lake Manyara National Park, famous for its dramatic groundwater forests and tree-climbing lions. Enjoy an intimate, hand-in-hand walk along the treetop canopy bridges high above the forest floor. Afterward, check into a spectacular, eco-luxury lodge featuring romantic glass-walled treehouse suites that look directly into the surrounding wilderness.\r\n\r\n4.Day 4: Ngorongoro Caldera Descent \u0026 Crater Rim Celebrations:Above the Clouds.Descend 2,000 feet into the Ngorongoro Crater floor\u2014a pristine volcanic caldera home to over 30,000 wild animals. Together, track the endangered black rhino to complete your Big Five checklist. Enjoy a private, secluded picnic lunch by the lakeside watching hippos splash. In the evening, ascend to your luxury crater-rim lodge for a special celebratory farewell dinner.\r\n\r\n5.Day 5: Cultural Souvenir Hunting \u0026 Departure:Fond Farewells.Enjoy a late, lazy breakfast overlooking the crater mist. On your smooth journey back to Arusha, make a stop at a premium local art gallery to pick up a beautiful Tanzanian carving or gemstone to commemorate your honeymoon. Your private vehicle will transfer you directly to Kilimanjaro International Airport (JRO) for your flight home or onward journey to Zanzibar.\r\n\r\nINCLUDED \u0026 EXCLUDED:\r\nWhat\u2019s Included in Your Honeymoon Safari:\r\n100% Private Safari: Exclusive use of a customized 4x4 Safari Land Cruiser with plush seating, pop-up roof, and charging stations.\r\n\r\nHoneymoon Touches: Complimentary bottle of sparkling wine, rose-petal room decorations, and a private bush sundowner setup.\r\n\r\nPremium Luxury Lodging: 4 nights staying in highly romantic, top-tier luxury tented camps and glass-walled suites.\r\n\r\nExpert Private Guide: A knowledgeable, professional English-speaking driver-guide dedicated to your pace.\r\n\r\nAll Entry Fees Covered: 100% of national park fees, conservation fees, and vehicle crater descent fees.\r\n\r\nFull Board Fine Dining: Sumptuous breakfasts, private wild picnics, and romantic multi-course lodge dinners.\r\n\r\nUncapped Refreshments: Chilled bottled mineral water, local juices, and soft drinks available at all times inside the vehicle.\r\n\r\nWhat\u2019s Excluded:\r\nInternational Airfare: Flights to and from Tanzania.\r\n\r\nTourist Entry Visas: Visas ($50 \u2013 $100 USD depending on your passport country).\r\n\r\nPremium Spirits \u0026 Champagne: Available at the lodges but billed separately (outside of your welcome wine).\r\n\r\nGratuities: Traditional tipping for your private guide and lodge hospitality staff.\r\n\r\nTravel Insurance: Personal medical emergency and trip cancellation insurance.\r\n\r\nHONEYMOON SAFARI FAQs\r\nFrequently Asked Questions By Couples\r\nQ: Can we combine this 5-day safari with a beach trip to Zanzibar?\r\n\r\nA: Yes, this is the most popular choice for honeymooners! We specialize in seamless \u0022Bush to Beach\u0022 transitions. On Day 5, we can easily transfer you directly to Arusha Airport or Kilimanjaro Airport for a short, 1-hour flight straight to Zanzibar Island, where a private transfer will be waiting to take you to a luxury beachfront resort. Just let us know and we will customize the add-on flight for you.\r\n\r\nQ: How much privacy will we actually have during the game drives and at the lodges?\r\n\r\nA: You will have absolute privacy. The safari vehicle is entirely yours\u2014there will be no other tourists sharing your space. At the lodges, we explicitly select luxury tented camps that feature highly spaced-out, private decks and villas. Your dinners can be set up privately on your room\u2019s deck or at an isolated table away from other guests upon request to ensure a peaceful, romantic atmosphere.\r\n\r\nQ: What should we pack if we want to dress up for romantic dinners?\r\n\r\nA: While daytime safari attire should be casual, comfortable, and neutral-colored, most luxury lodges have a relaxed yet elegant dress code for dinner. We recommend packing a couple of smart-casual outfits (a light summer dress, smart linen shirts, or trousers) for your evening candlelit dinners. Don\u0027t forget a warm jacket or pashmina, as the evenings on the Ngorongoro crater rim can get quite cool!","long_description":null,"includes":null,"meta_title":null,"meta_description":null,"meta_keywords":null,"created_at":"2026-07-11T09:43:02.000000Z","updated_at":"2026-07-11T09:43:16.000000Z"},{"id":64,"name":"4-Day Tanzania Safari Adventure","slug":"4-day-tanzania-safari-adventure","category":"Multi-Day Safari","duration":"4-Days","price":"1850.00","price_adult":"1850.00","price_child":"0.00","status":"Published","image":"https:\/\/res.cloudinary.com\/aenplcpl\/image\/upload\/v1783598502\/Screenshot_2026-07-09_045347_tvm3r5.png","desc":"Best 4-Day Tanzania Safari Adventure\r\nYour safari begins with your arrival in Tanzania and a warm welcome in Arusha, where you\u2019ll be transferred to the tranquil Ngare Sero Mountain Lodge. Nestled at the foot of Mount Meru, this lodge offers a peaceful start to your journey with views of Kilimanjaro and lush gardens. On the second day, you\u2019ll head to Tarangire National Park, known for its impressive population of elephants, tree-climbing lions, and ancient baobab trees. After a full day of game viewing, you\u2019ll continue to Karatu where you\u2019ll check into the scenic and serene Farm of Dreams Lodge, offering beautiful views of the highlands and personalized hospitality. who want to experience the beauty and diversity of Tanzania in a short amount of time, this tour combines exceptional game viewing, rich cultural experiences, and high-end accommodation. You\u2019ll journey from Arusha to Tarangire National Park and the world-renowned Ngorongoro Crater, creating lasting memories of wild landscapes and majestic animals all in just four days.\r\n\r\nTour Itinerary:\r\nDay 1: Arrival in Tanzania\r\nYour journey begins as you arrive at Kilimanjaro International Airport, where you\u2019ll be met by a professional driver-guide and transferred to your accommodation in Arusha. Nestled in a serene forest estate at the foot of Mount Meru, Ngare Sero Mountain Lodge is one of Tanzania\u2019s most unique and tranquil lodges. With a beautiful garden, private lake, colobus monkeys, and clear views of Mount Kilimanjaro, the lodge offers a peaceful retreat after your travels. Spend the rest of your day relaxing, enjoying freshly prepared meals, and soaking in the natural ambiance as you prepare for the adventures ahead.\r\n\r\nDay 2: Arusha to Tarangire National Park\r\nAfter an early breakfast, your safari adventure officially begins as you drive to Tarangire National Park, one of the most diverse ecosystems in Northern Tanzania. The park is known for its large herds of elephants, ancient baobab trees, and rich wildlife population including lions, leopards, giraffes, and hundreds of bird species. You\u2019ll enjoy a full-day game drive, stopping for a picnic lunch in a scenic spot within the park. Later in the afternoon, continue your journey to the town of Karatu, located near the Ngorongoro Highlands. Here, you\u2019ll check in at Farm of Dreams Lodge, a comfortable and scenic lodge with beautiful views of rolling hills, flower gardens, and well-designed cottages offering relaxation and great hospitality.\r\n\r\nDay 3: Ngorongoro Crater Exploration\r\nRise early for a day filled with wonder as you descend 600 meters into the Ngorongoro Crater one of the world\u2019s largest intact volcanic calderas and a UNESCO World Heritage Site. Known as the \u0022Eden of Africa,\u0022 the crater is home to over 25,000 wild animals, including endangered black rhinos, massive herds of wildebeest and zebras, prides of lions, and elusive leopards. The ecosystem within the crater offers one of the best chances to see the Big Five in a single day. After a picnic lunch by the hippo pool, you\u2019ll exit the crater and begin your scenic drive back to Arusha. Once again, you\u0027ll enjoy a final overnight stay at the peaceful Ngare Sero Mountain Lodge, where you can enjoy a quiet evening by the fire or take a walk around the property\u2019s private forest trails.\r\n\r\nDay 4: Departure Day\r\nDepending on your departure time, you may have the opportunity to enjoy a relaxed morning at the lodge, take a walk around the gardens, or visit Arusha town for a cultural experience or souvenir shopping. Your driver will transfer you to Kilimanjaro International Airport for your onward journey, marking the end of your unforgettable 4-Day Tanzania Safari Adventure. Though short, this safari offers an incredible variety of wildlife, stunning landscapes, and high-quality service the perfect introduction to Tanzania\u2019s natural wonders.\r\n\r\nThe third day of your 4-Day Tanzania Safari Adventure takes you to one of the world\u2019s most stunning natural wonders \u2014 the Ngorongoro Crater. Descend 600 meters into this UNESCO World Heritage Site to discover a breathtaking ecosystem that\u2019s home to black rhinos, hippos, hyenas, lions, zebras, and more. After a rewarding day of wildlife encounters, you\u0027ll return to Ngare Sero Mountain Lodge for your final night in Tanzania. On day four, depending on your flight schedule, you may enjoy a relaxed morning before transferring to the airport, filled with unforgettable memories of your luxurious yet time-efficient safari. Whether you\u2019re a couple, solo traveler, or on a quick getaway, this 4-Day Tanzania Safari Adventure is the perfect blend of adventure and comfort. With experienced guides, seamless logistics, and stays at some of the best lodges in the region, this itinerary delivers big on experience while keeping your schedule flexible. It\u2019s the ideal choice for those looking to witness the magic of Tanzania\u2019s wildlife and landscapes without committing to a longer itinerary.\r\n\r\nSafari Package Inclusions\r\nComfortable Private 4x4 Safari Vehicle with an English-Speaking Guide\r\nAirport Transfer Before Trip and After Trip\r\nAll Park Fees, Game Drives, and Crater Tour\r\nFull Board Accommodation in Tented Camps (Double Room)\r\nDrinking Water During the Safari\r\nAll Accommodation During the Safari in Standard Mid-Range Level (Full Board)\r\nUnlimited Mileage for Game Drives\r\nAll Government Taxes and Park Entrance Fees\r\nAll Meals (3 Per Day) During the Safari\r\nAccommodation\r\n24\/7 Customer Support During Your Safari\r\n\r\nSafari Package Exclusions\r\nInternational \u0026 Domestic Flights\r\nVisa Fees\r\nDomestic Flight (On request with extra fee)\r\nTravel Insurance (highly recommended)\r\nGratuities for Your Safari Guide ($30\u2013$50 per day per vehicle)\r\nBalloon Ride (600 USD per person)","long_description":null,"includes":null,"meta_title":null,"meta_description":null,"meta_keywords":null,"created_at":"2026-07-11T09:13:05.000000Z","updated_at":"2026-07-11T09:13:22.000000Z"},{"id":63,"name":"1-Day VIP Serval Wildlife Sanctuary Experience","slug":"1-day-vip-serval-wildlife-sanctuary-experience","category":"Day Trip","duration":"Full day","price":"240.00","price_adult":"240.00","price_child":"120.00","status":"Published","image":"https:\/\/res.cloudinary.com\/aenplcpl\/image\/upload\/v1783352743\/several_wildlife_teejza.jpg","desc":"1-Day VIP Serval Wildlife Sanctuary Experience\r\nPricing: From $240 Per Person | Duration: 1 Day (Approx. 2 Hours inside the park) | Pick-up\/Drop-off: Arusha or Moshi\r\n\r\nTOUR OVERVIEW:\r\nTouch the Wild: An Intimate Encounter with Africa\u2019s Most Iconic Wildlife\r\nStep away from behind the binoculars and experience a magical, face-to-face connection with nature. Tucked away in the scenic Siha District of the Kilimanjaro region, the Serval Wildlife Sanctuary is a premium eco-tourism haven dedicated to ethical wildlife conservation and the care of rescued animals. This exclusive day trip is perfect for couples, solo travelers, and families who want an immersive, up-close wildlife encounter that traditional game drives simply cannot offer.\r\n\r\nImagine standing hand-in-hand with towering Masai giraffes as they gently eat from your palms, watching the unmatched grace of a cheetah up close, or capturing the playful energy of blue monkeys and elands. Every animal at the sanctuary roams freely in beautifully curated, natural ecosystems, with the only exceptions being the lions, who are kept in vast, reinforced safety enclosures for your absolute peace of mind. Led by expert conservation guides, this highly photogenic tour turns your wildlife dreams into an unforgettable, touchable reality.\r\n\r\nTOUR ITINERARY\r\n1.08:00 AM \u2013 Private Transfer \u0026 Scenic Drive:Morning Pickup.Your private driver-guide will collect you from your hotel in Arusha or Moshi in a comfortable vehicle. Relax and enjoy a smooth, scenic drive through the beautiful Tanzanian countryside, passing local homesteads and coffee farms with breathtaking views of Mount Kilimanjaro in the distance.\r\n2.10:00 AM \u2013 Arrival \u0026 Eco-Sanctuary Briefing:VIP Welcome.Upon arrival at the luxury eco-resort, you will be welcomed by professional resident conservationists. Enjoy a brief orientation about the sanctuary\u0027s strict ethical standards, wildlife protection laws, and tips on how to interact safely and respectfully with the free-roaming animals.\r\n3.10:30 AM \u2013 Hand-Feeding \u0026 Photography Session:The Encounter.Spend two magical hours exploring the sanctuary. Under the close, supervised guidance of wildlife experts, you will have the once-in-a-lifetime opportunity to hand-feed gentle giraffes, pet friendly elands, and stand just feet away from majestic cheetahs. Bring your camera or smartphone fully charged\u2014this is the ultimate African photo-shoot.\r\n4.12:30 PM \u2013 Premium Lunch with a View:Wild Dining.Head over to the upscale sanctuary restaurant. Savor a delicious, freshly prepared lunch box or select from their premium menu, enjoying your meal while watching the animals move peacefully across the beautiful savannah backdrop.\r\n5.02:30 PM \u2013 Departure \u0026 Afternoon Drop-off:The Return.After a final look at this pristine paradise and a stop at the boutique gift shop for local artisan souvenirs, board your private vehicle for a relaxing ride back to town, arriving at your hotel in time for dinner with a camera full of jaw-dropping memories.\r\n\r\nINCLUDE \u0026 EXCLUDED:\r\nWhat\u2019s Included:\r\nOfficial Entrance Fees: 100% of the Serval Wildlife non-resident day-visit permit fees\r\n\r\nPrivate Round-Trip Transfers: Pick-up and drop-off directly from your hotel in Moshi or Arusha.\r\n\r\nProfessional Guiding: Accompanied by a fluent English-speaking driver-guide and dedicated sanctuary conservationists.\r\n\r\nGourmet Lunch Box: A freshly packed lunch with vegetarian, vegan, and gluten-free options available upon request.\r\n\r\nComplimentary Refreshments: Unlimited bottled mineral water provided inside the vehicle throughout the day.\r\n\r\nAnimal Interaction: Supervised access to feed and photograph the free-roaming wildlife.\r\n\r\nWhat\u2019s Excluded:\r\nLodge Overnight Accommodation: (This is a day trip, though luxury villa overnight upgrades can be arranged).\r\n\r\nPremium Fine Dining: Purchases from the main resort restaurant menu or alcohol.\r\n\r\nGratuities: Optional tips for your private driver and the sanctuary keepers.\r\n\r\nTravel Insurance: Personal medical and trip protection coverage.\r\n\r\nSERVAL WILDLIFE FAQs:\r\nFrequently Asked Questions\r\nQ: Is it ethical to touch and feed the animals here?\r\n\r\nA: Yes, entirely. Serval Wildlife operates under strict eco-conservation rules. The animals at the sanctuary have been ethically rescued from orphanhood or human-wildlife conflict zones and are fully habituated to human presence. The interactions are non-intrusive, strictly supervised by trained handlers, and designed to fund the sanctuary\u2019s ongoing reforestation and wildlife rehabilitation programs.\r\n\r\nQ: Are children allowed to join the day visit?\r\n\r\nA: Yes! Children of all ages are warmly welcomed under strict parental or guardian supervision. It is an incredibly educational and inspiring experience for young minds. Please note that for absolute safety, children are kept at a slightly further distance from the apex predators like lions and cheetahs, but they can fully participate in feeding the friendly giraffes and antelopes.\r\n\r\nQ: Do I need to book this day trip months in advance?\r\n\r\nA: While day-visit tickets can technically be purchased at the gate, we highly recommend booking your tour with us at least a few weeks in advance. This allows us to secure your private safari vehicle, schedule the smoothest pickup windows to beat the crowds, and coordinate any specific dietary requirements for your gourmet lunch boxes.","long_description":null,"includes":null,"meta_title":null,"meta_description":null,"meta_keywords":null,"created_at":"2026-07-10T15:02:32.000000Z","updated_at":"2026-07-10T16:59:37.000000Z"},{"id":62,"name":"6 Days Ultimate Wilderness Camping Safari","slug":"6-days-ultimate-wilderness-camping-safari","category":"Multi-Day Safari","duration":"6-Days","price":"1800.00","price_adult":"1800.00","price_child":"0.00","status":"Published","image":"https:\/\/res.cloudinary.com\/aenplcpl\/image\/upload\/v1783691928\/Screenshot_2026-07-10_065323-Picsart-AiImageEnhancer_nexqyd.png","desc":"Explore Tanzania in 6 Days of Camping Safari\r\n Duration: 6 Days\r\n Tour Type : Private\r\n Group Size : 10-20 People\r\n\r\n6 Days Tanzania Camping Safari - Explore Serengeti \u0026 Ngorongoro\r\nIf you\u0027re looking for an authentic 6 Days Tanzania Camping Safari, this is the perfect journey to explore Tanzania\u0027s most iconic wildlife parks. The adventure includes visits to Serengeti National Park, Ngorongoro Crater, Lake Manyara, and Tarangire National Park. This safari offers an unmatched experience of Africa\u0027s natural wonders while camping under the stars in the heart of these stunning regions.\r\n\r\nThroughout your 6 Days Tanzania Camping Safari, you\u0027ll have the chance to witness the Big Five in their natural habitat and see the incredible landscapes that make these parks world-renowned. From the open plains of the Serengeti to the lush wildlife haven of Ngorongoro Crater, you\u2019ll experience the ultimate African adventure.\r\n\r\nWhy Choose the 6 Days Tanzania Camping Safari?\r\nExplore Iconic Tanzania Parks: Visit the Serengeti, Ngorongoro, Lake Manyara, and Tarangire on this comprehensive 6 Days Tanzania Camping Safari.\r\nAffordable \u0026 Authentic Safari Experience: Enjoy the adventure of camping in the heart of Tanzania\u2019s wilderness while staying within your budget.\r\nGreat for Wildlife Enthusiasts: Perfect for spotting lions, elephants, giraffes, rhinos, and other wildlife.\r\nUnmatched Adventure: See the world-famous Great Migration in the Serengeti and the diverse wildlife of Ngorongoro.\r\nWhat to Expect from the 6 Days Tanzania Camping Safari\r\nThis 6 Days Tanzania Camping Safari will give you a truly immersive experience. Each night, you\u0027ll camp at well-equipped campsites close to nature. During the day, you\u0027ll explore the rich wildlife and diverse landscapes of Tanzania\u0027s national parks. Comfortable 4x4 safari vehicles will be your mode of transport, ensuring an unforgettable and safe journey.\r\n\r\nTour Itinerary:\r\nDay 1 : Arusha To Tarangire National Park\r\nAfter breakfast, you will meet an office representative for a briefing about your safari, then take 2 hours\u2019 drive to Tarangire National Park. Along the way, you will take in the scenic views of the grassland plains speckled with the ancient baobab trees, traditional Maasai houses, and cattle being herded by the nomadic people. You will see all that Tarangire National Park is known for \u2013 the famous ancient baobab trees, tree climbing pythons, and the largest concentration of African elephants, lions, leopards, cheetah. After a picnic lunch in midday, you will resume your afternoon game drive then drive to the hotel\/lodge\/Camp for dinner and an overnight stay.\r\n\r\nDistance: 150 km\r\nGame viewing time: 6-7 hours\r\nTransport: 4wd Custom Safari Vehicle\r\nMeals included: Breakfast, Lunch and Dinner\r\n\r\nDay 2 : Tarangire To Lake Manyara National Park\r\nYou\u0027ll depart from Tarangire after breakfast and begin the drive to Lake Manyara National Park. The journey takes approximately two hours, you\u0027ll enter Lake Manyara National Park. The park is truly a photographer\u0027s playground and offers some of the best game viewing in the world. You can expect to see many of Africa\u0027s most well-known animals, with the tree-climbing lions a particular treat. These proud predators lounge in acacia trees practically begging to be photographed. Bird-watchers will find Lake Manyara is an absolute delight, with a huge variety of birds on display in the park. Even the novice can expect to be amazed by large flamingo flocks, circling birds of prey, and the brightly coloured lilac breasted roller.\r\n\r\nDistance: 150 km\r\nGame viewing time: 6-7 hours\r\nTransport: 4wd Custom Safari Vehicle\r\nMeals included: Breakfast, Lunch and Dinner\r\n\r\nDay 3 : Lake Manyara To Serengeti National Park\r\nAfter a breakfast, you will drive to Serengeti National Park. This drive will take you through the Ngorongoro Conservation Area so you will be able to view the Ngorongoro Crater on the way. This drive will take around six hours depending on the game on the way because sometimes there is a high concentration of game on the southern plains. You may have a chance to stop at the Ndutu area depends on the time. You will stop for a picnic lunch on the way then proceeds to your camp\/lodge with a game drive en-route arriving in the evening.\r\n\r\nDistance: 243 km\r\nGame viewing time: 6-7 hours\r\nTransport: 4wd Custom Safari Vehicle\r\nMeals included: Breakfast, Lunch and Dinner\r\n\r\nDay 4 : Serengeti National Park (North) Wildebeest Migration Watching\r\nRise up early, so that you can depart your lodge for a full day game viewing, moving with the herds across the plains exploring the Great Wildebeest Migration in the northern part toward the Mara River. An impressive experience to see all those hoofed mammals, followed by predators, migrate in a clockwise circle every year. If you want also to spot the Big Five animals (lion, elephant, leopard, rhino, and buffalo) all in one park, the Serengeti is the place to be. In the evening, you will be transferred back to your camp\/Lodge for dinner and an overnight stay.\r\n\r\nDistance: 243 km\r\nGame viewing time: 6-7 hours\r\nTransport: 4wd Custom Safari Vehicle\r\nMeals included: Breakfast, Lunch and Dinner\r\n\r\nDay 5 : Serengeti To Ngorongoro Conservation Area\r\nAfter breakfast, you will proceed with packed lunch for early morning game drive between 08:00 - 14:30 hours. Then, will depart to the Ngorongoro Conservation Area while enjoying a game drive en-route viewing along the way to where you will have dinner and overnight.\r\n\r\nDistance: 135 km\r\nGame viewing time: 6-7 hours\r\nTransport: 4wd Custom Safari Vehicle\r\nMeals included: Breakfast, Lunch and Dinner\r\n\r\nDay 6 : Ngorongoro Conservation Area\r\nAfter an early breakfast, you will descend over 600 meters into the crater to view wildlife. In midday, you will have your lunch at the base of the crater. Ngorongoro is one of the 8th Wonders of the World, the crater is a very important refuge for a variety of wildlife. Most commonly seen are big bulls of elephants and the vulnerable species of black rhinos. The crater is also home to big herds of wildebeest and zebras, thomson and grant gazelles, hippopotamus, African cape buffalos, prides of lions, hyenas, serval cats, golden and black-backed jackals, and close to 400 species of birds. Later on, will follow the transfer back to Arusha town, or directly to the airport.\r\n\r\nDistance: 185 km\r\nGame viewing time: 5-6 hours\r\nTransport: 4wd Custom Safari Vehicle\r\n\r\nIncluded:\r\n4x4 Safari Car\r\nPark fees\r\nAll activities (unless labeled as optional\r\nAll accommodation as stated in the itinerary\r\nAll transportation (unless labeled as optional)\r\nAll Taxes\/VAT\r\nRoundtrip airport transfer\r\nAll Meals (as specified in the day-by-day section)\r\nDrinking water on all days\r\n\r\nNot Included:\r\nSleeping bag\r\nFlights\r\nOptional activities\r\nAlcoholic and soft drinks\r\nVisa fees\r\nTips\r\nPersonal spending money for souvenirs etc.\r\nTravel insurance","long_description":null,"includes":null,"meta_title":null,"meta_description":null,"meta_keywords":null,"created_at":"2026-07-10T14:22:42.000000Z","updated_at":"2026-07-10T14:24:40.000000Z"},{"id":61,"name":"Explore 4 Days Ndutu the Great Migration Season","slug":"explore-4-days-ndutu-the-great-migration-season","category":"Multi-Day Safari","duration":"4-Days","price":"1600.00","price_adult":"1600.00","price_child":"0.00","status":"Published","image":"https:\/\/res.cloudinary.com\/aenplcpl\/image\/upload\/v1783692759\/Screenshot_2026-07-10_070152_yhvxhc.png","desc":"Detailed 4 Days Ndutu Migration Safari Adventure\r\n Duration: 4 Days\r\n Tour Type : Group join \/ Private\r\n Group Size : 4+ People\r\n\r\nThe 4 Days Ndutu Migration Safari is a remarkable adventure designed specifically to enhance your chances of witnessing the great Serengeti wildebeest migration. Taking place between mid-December and April, this tour focuses on the Southern Serengeti and the Ndutu areas, which are prime locations during January, February, and March for viewing thousands of wildebeest grazing on the lush, short grass plains.\r\n\r\nDuring this time, predator action is at its peak, with excellent opportunities to see cheetahs, lions, hyenas, and leopards in action. It\u2019s an adrenaline-filled spectacle that showcases the dramatic predator-prey interactions unique to this region and season.\r\n\r\nHighlights of This Safari\r\nPrime Migration Season: Experience the great wildebeest migration during the calving season in Ndutu.\r\nPredator Encounters: Witness the thrilling hunts of cheetahs, lions, leopards, and hyenas.\r\nScenic Southern Serengeti: Explore the picturesque short grass plains, perfect for photography and wildlife viewing.\r\nImmersive Experience: Spend four days in the heart of one of the most active wildlife regions in Tanzania.\r\nWhy Choose This Safari?\r\nPerfect for wildlife enthusiasts and photographers, the Ndutu Migration Safari offers an unmatched opportunity to immerse yourself in one of nature\u0027s greatest spectacles. From the incredible wildebeest herds to the intense predator activity, this safari promises a memorable journey filled with drama and beauty.\r\n\r\nTour Itinerary:\r\nDay 0: Arrival in Arusha\r\nYou will be picked up at the Kilimanjaro International Airport and transferred to your arranged hotel in Arusha town. You will meet your guide who will brief you on your Africa Safari tour. You will spend the rest of your time at your won leisure and relaxation.\r\nACCOMMODATION: KARIBU HERITAGE HOUSE ARUSHA\r\n\r\nDay 1 :Arusha - Serengeti South - Ndutu Area\r\nAfter breakfast, you\u2019ll be picked up from your Hotel in Arusha and then transferred to Arusha airport for your short time flight (50mins) to Serengeti North Ndutu Area. Upon arrival, you will meet your driver guide for a full day game drive in Ndutu to view the migration. In the evening, we\u2019ll drive to the camp for dinner and overnight. Lake Ndutu area, situated in the Ngorongoro conservation area, part of the southern Serengeti eco-system. Lake Ndutu is alkaline, like most of the other Rift lakes, however, the water is still drinkable and used by a wide array of local wildlife. The majority of the wildebeest migration can normally be found on the short-grass plains from December to April. The area is usually heavily populated with elephant, birds and resident game.\r\nMeals: Breakfast,Lunch,Dinner\r\nAccommodation: Public Camp site\r\nAccommodation Lodge Option :Acacia Migration Camps.\r\n\r\nDay 2 : Serengeti South (Ndutu Area)\r\nYou\u2019ll have an early morning game drive at Ndutu Area and in the afternoon, back to the Camp for Lunch. You\u2019ll also enjoy an evening game, then back to the camp for dinner and overnight. Thorough explanation about this great world event that Tanzania is proud of will be provided. And in the evening as the sun goes down, Tanzania\u2019s oldest and most popular national park, also a world heritage site and recently proclaimed a 7th worldwide wonder, the Serengeti is famed for its annual migration\r\nMeals: Breakfast,Lunch,Dinner\r\nAccommodation: Public Camp site\r\nAccommodation Lodge Option :Acacia Migration Camps\r\n\r\nDay 3 : Ndutu Area - Central Serengeti\r\nEarly in the morning, enjoy a hot air balloon safari, then back to the camp for your breakfast followed with an afternoon game drive en route to Serengeti Central (Seronera). The park is well known for its healthy stock of other resident wildlife, particularly the \u0022big five\u0022, named for the five most prized trophies taken by hunters: Lion: the Serengeti is believed to hold the largest population of lions in Africa due in part to the abundance of prey species. More than 3,000 lions live in this ecosystem. African Leopard: these reclusive predators are commonly seen around.\r\nMeals: Breakfast,Lunch,Dinner\r\nAccommodation: Public Camp site\r\nAccommodation Lodge Option :Acacia Migration Camps\r\nHOT AIR BALLOON PER PERSON COST $546 (price are separate from the total price above)\r\n\r\nDay 4 : Serengeti \u2013 Arusha (Departure)\r\nEnjoy a morning game drive, then back to the camp for breakfast. Afterwards, we\u2019ll drive back to Arusha. You\u2019ll be dropped off to your Hotel booked on own arrangement.\r\nMeals: Breakfast,Lunch\r\n\r\nACCOMMODATION: KARIBU HERITAGE HOUSE ARUSHA\r\n\r\nIncluded:\r\nSharing 4x4 Safari Safari jeep Car\r\n2 Night Accommodation in Arusha before and After Safari\r\nAll Park fees\r\nSleeping bag and tent in good conditions\r\nAll activities (unless labeled as optional\r\nAll Camping accommodation as stated in the itinerary\r\n2 Airport transifer before and After Safari (unless labeled as optional)\r\nAll Taxes\/VAT\r\nAll Meals while on the safari (as specified in the day-by-day section)\r\nDrinking water on all days\r\n\r\nNot Included:\r\nFlights\r\nOptional activities\r\nAlcoholic and soft drinks\r\nVisa fees\r\nTips\r\nPersonal spending money for souvenirs etc.\r\nTravel insurance","long_description":null,"includes":null,"meta_title":null,"meta_description":null,"meta_keywords":null,"created_at":"2026-07-10T14:15:10.000000Z","updated_at":"2026-07-10T14:15:17.000000Z"},{"id":60,"name":"3-Days Premium Camping Safari Adventures","slug":"3-days-premium-camping-safari-adventures-1","category":"Multi-Day Safari","duration":"3-Days","price":"900.00","price_adult":"900.00","price_child":"0.00","status":"Published","image":"https:\/\/res.cloudinary.com\/aenplcpl\/image\/upload\/v1783690457\/Screenshot_2026-07-10_063014_hyg7dk.png","desc":"Detailed 3 Days Camping Safari:\r\n Duration: 3 Days\r\n Tour Type:Private\r\n Group Size: 10-20 People\r\n\r\n3 Days, 2 Nights Tanzania Budget Camping Safari: This exciting camping safari takes you to the heart of Tanzania\u2019s wildlife, exploring both the Serengeti National Park and the Ngorongoro Crater. Witness the awe-inspiring wildebeest migration in the Serengeti and immerse yourself in the Ngorongoro Crater\u0027s dense wildlife habitats, all while enjoying a budget-friendly camping experience.\r\n\r\nSpend one night at the Seronera Public Campsite in the Serengeti, where you\u0027ll have the chance to spot the famous big cats and herds of wildebeest, zebras, and gazelles. On the second night, stay at the Simba Campsite near the Ngorongoro Crater and conclude your adventure with a game drive in the crater, known for its concentrated wildlife and breathtaking scenery.\r\n\r\nWhy Choose This Camping Safari?\r\nAffordable Adventure: Enjoy a thrilling safari experience without stretching your budget.\r\nIconic Destinations: Visit the Serengeti National Park and the Ngorongoro Crater, two of Tanzania\u2019s top wildlife spots.\r\nWildebeest Migration: Witness the world-famous migration and other incredible wildlife encounters.\r\nImmersive Camping: Stay in public campsites close to nature, offering an authentic safari experience.\r\n\r\nTour Itinerary\r\nDay 1 : Safari From Arusha To Serengeti.\r\nDepart from Arusha to Serengeti National Park after breakfast via Ngorongoro\/Oldupai Gorge where the fossil of first humans called Zinjanthropus where discovered by Dr. Lousy Leakey and Mary Leakey while accompanied with lunch boxes. Continue with afternoon game drive. Dinner and overnight at Seronera campsite.\r\n\r\nDay 2 : Half Day Game Drive In Serengeti To Ngorongoro\r\nBreakfast is followed by half day game drive in Serengeti National Park. This Park covers an area of 14,763sq km. The name Serengeti come from Maasai language and means \u201cendless plains\u201d. Here wildlife like Lion, Thompson and Grant Gazelle, Zebra, Buffalo, Hippo, Elephant \u0026 Cheetah. Drive to Ngorongoro for overnight at Simba campsite. Meal plan Full Board.\r\n\r\nDay 3 : Ngorongoro Crater-Arusha\r\nAfter breakfast you depart to Ngorongoro Crater at 6:30. Here you come across unique attractions near this historical caldera where man and wildlife are living together without harming one another. Get a chance to observe wildlife including Lion, Elephant, Hippo, Wildebeest, Warthog and Zebra, and see also lake Magadi, Lerai Forest and Ngoitokito swamp; while accompanied with lunch boxes. After lunch start driving to Arusha\r\n\r\nIncluded:\r\n4x4 Safari Car\r\nPark fees\r\nAll activities (unless labeled as optional\r\nAll accommodation as stated in the itinerary\r\nAll transportation (unless labeled as optional)\r\nAll Taxes\/VAT\r\nRoundtrip airport transfer\r\nAll Meals (as specified in the day-by-day section)\r\nDrinking water on all days\r\n\r\nNot Included:\r\nFlights\r\nOptional activities\r\nAlcoholic and soft drinks\r\nVisa fees\r\nTips\r\nPersonal spending money for souvenirs etc.\r\nTravel insurance","long_description":null,"includes":null,"meta_title":null,"meta_description":null,"meta_keywords":null,"created_at":"2026-07-10T13:48:21.000000Z","updated_at":"2026-07-10T13:48:26.000000Z"}];
let currentFilter = 'all';
let currentSearch = '';

function escapeQuotes(str) {
    return str.replace(/'/g, '\\\'').replace(/"/g, '\\"');
}

function setDestFilter(filter) {
    currentFilter = filter;
    document.querySelectorAll('#destFilterChips .chip').forEach(c => c.classList.toggle('active', c.dataset.filter === filter));
    renderFilteredDestinations();
}

function filterDestinations(search) {
    currentSearch = search.toLowerCase();
    renderFilteredDestinations();
}

function renderFilteredDestinations() {
    document.querySelectorAll('#destinationsBody tr').forEach(tr => {
        if (!tr.dataset.id) return;
        let match = true;
        if (currentFilter !== 'all') {
            match = (tr.dataset.category === currentFilter || tr.dataset.status === currentFilter);
        }
        if (currentSearch) {
            match = match && tr.dataset.name.includes(currentSearch);
        }
        tr.style.display = match ? 'table-row' : 'none';
    });
}

function openDestinationModal(id = null) {
    const title = document.getElementById('destModalTitle');
    title.textContent = id ? 'Edit destination' : 'Add destination';
    const form = document.getElementById('destForm');
    const submitBtn = document.getElementById('destSubmitBtn');
    submitBtn.textContent = id ? 'Update destination' : 'Save destination';

    if (id) {
        const destData = destinationsData.find(d => d.id === id);
        if (destData) {
            document.getElementById('destMethod').value = 'PUT';
            document.getElementById('destId').value = destData.id;
            document.getElementById('destName').value = destData.name;
            document.getElementById('destCategory').value = destData.category;
            document.getElementById('destStatus').value = destData.status;
            document.getElementById('destDuration').value = destData.duration;
            document.getElementById('destPriceAdult').value = destData.price_adult;
            document.getElementById('destPriceChild').value = destData.price_child;
            document.getElementById('destImage').value = destData.image;
            document.getElementById('destDesc').value = destData.desc;
        }
    } else {
        document.getElementById('destMethod').value = '';
        document.getElementById('destId').value = '';
        document.getElementById('destName').value = '';
        document.getElementById('destCategory').value = 'Day Trip';
        document.getElementById('destStatus').value = 'Published';
        document.getElementById('destDuration').value = '';
        document.getElementById('destPriceAdult').value = '';
        document.getElementById('destPriceChild').value = '';
        document.getElementById('destImage').value = '';
        document.getElementById('destDesc').value = '';
    }
    openModal('destModalBackdrop');
}

function editDestination(id) {
    openDestinationModal(id);
}

async function handleSubmit(event) {
    event.preventDefault();
    const form = document.getElementById('destForm');
    const submitBtn = document.getElementById('destSubmitBtn');
    const id = document.getElementById('destId').value;
    submitBtn.disabled = true;
    submitBtn.textContent = id ? 'Updating...' : 'Saving...';
    
    const formData = new FormData(form);
    let url = "https://tanzaniadailytoursandsafari.com/live/destinations";
    let method = 'POST';
    
    if (id) {
        url = `/live/destinations/${id}`;
        formData.append('_method', 'PUT');
    }
    
    try {
        const response = await fetch(url, {
            method: method,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': formData.get('_token'),
            },
            body: formData,
        });
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Server Error:', response.status, errorText);
            toast(`Error: ${response.status}`, 'error');
            return;
        }
        
        const data = await response.json();
        
        if (data.success) {
            toast(data.message, 'success');
            window.location.reload();
        } else if (data.errors) {
            const errorMessages = Object.values(data.errors).flat().join(', ');
            toast(errorMessages, 'error');
        } else {
            toast(data.message || 'Something went wrong!', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        toast('Something went wrong! Please check the console.', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = id ? 'Update destination' : 'Save destination';
    }
}

function confirmDeleteDest(id, name) {
    document.getElementById('confirmText').textContent = 'Delete "' + name + '"? This will remove it from the live site listings.';
    pendingDeleteFunc = async () => {
        try {
            const response = await fetch(`/live/destinations/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': "w3Vlfvw2Bh8VfqolGpMryy5mbgT3npZg5cr409mV",
                    'Content-Type': 'application/json',
                },
            });
            
            const data = await response.json();
            
            if (data.success) {
                toast(data.message, 'success');
                window.location.reload();
            }
        } catch (error) {
            console.error('Error:', error);
            toast('Something went wrong!', 'error');
        }
    };
    openModal('confirmModalBackdrop');
}

function executeDelete() {
    if (pendingDeleteFunc) {
        pendingDeleteFunc();
    }
    closeModal('confirmModalBackdrop');
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    renderFilteredDestinations();
});
</script>

            </div>
        </div>
    </div>
    <div id="toastHost"></div>
    <script>
        // Auto-logout timer (5 minutes in milliseconds)
        const AUTO_LOGOUT_TIME = 5 * 60 * 1000;
        let autoLogoutTimer;

        // Reset the auto-logout timer on user activity
        function resetAutoLogoutTimer() {
            clearTimeout(autoLogoutTimer);
            autoLogoutTimer = setTimeout(() => {
                // Clear the session on the server side by redirecting to login
                window.location.href = "https://tanzaniadailytoursandsafari.com/live/login";
            }, AUTO_LOGOUT_TIME);
        }

        // Add event listeners for user activity
        ['click', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
            document.addEventListener(event, resetAutoLogoutTimer, true);
        });

        // Initialize the timer when the page loads
        resetAutoLogoutTimer();

        function toggleSidebar(){
            if(window.innerWidth <= 900){
                document.getElementById('sidebar').classList.toggle('mobile-open');
                document.getElementById('mobileOverlay').classList.toggle('show');
            } else {
                document.getElementById('sidebar').classList.toggle('collapsed');
            }
        }
        function closeMobileSidebar(){
            document.getElementById('sidebar').classList.remove('mobile-open');
            document.getElementById('mobileOverlay').classList.remove('show');
        }
        function toggleSbDrop(el){
            const drop = el.closest('.sb-drop');
            const wasOpen = drop.classList.contains('open');
            document.querySelectorAll('.sb-drop').forEach(d => d.classList.remove('open'));
            if(!wasOpen) drop.classList.add('open');
        }

        function toast(msg, type='default'){
            const host = document.getElementById('toastHost');
            const el = document.createElement('div');
            el.className = 'toast ' + (type==='success'?'success':type==='error'?'error':'');
            el.innerHTML = (type==='success' ? '<svg viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><path d=\"M20 6 9 17l-5-5\"></path></svg>' : '') + '<span>'+msg+'</span>';
            host.appendChild(el);
            setTimeout(()=>{ el.style.opacity='0'; el.style.transform='translateX(20px)'; el.style.transition='all .25s'; setTimeout(()=>el.remove(),250); }, 3000);
        }

        function openModal(id){ document.getElementById(id).classList.add('show'); }
        function closeModal(id){ document.getElementById(id).classList.remove('show'); }
    </script>
</body>
</html>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="w3Vlfvw2Bh8VfqolGpMryy5mbgT3npZg5cr409mV">
    <title>Dashboard · Tanzania Daily Tours & Safari</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        :root{
            --sand-50:#FBF7EF;
            --sand-100:#F4ECDC;
            --sand-200:#E9DCC0;
            --coffee-900:#2A1B10;
            --coffee-800:#3B2718;
            --coffee-700:#4D3422;
            --coffee-500:#7A5C42;
            --coffee-300:#A98968;
            --terracotta-600:#C2592B;
            --terracotta-500:#D06B3A;
            --terracotta-100:#F6E1D3;
            --acacia-600:#5E6E3F;
            --acacia-500:#7A8450;
            --acacia-100:#E2E7D4;
            --gold-500:#D4A24C;
            --gold-100:#F7E9CB;
            --ink:#241408;
            --ink-soft:#6B5A48;
            --line:#E4D7C2;
            --white:#FFFFFF;
            --danger:#B33A3A;
            --danger-100:#F6DCDA;
            --success:#3F6B3F;
            --radius-sm:8px;
            --radius-md:14px;
            --radius-lg:20px;
            --shadow-sm:0 1px 2px rgba(42,27,16,.08);
            --shadow-md:0 8px 24px rgba(42,27,16,.10);
            --shadow-lg:0 20px 48px rgba(42,27,16,.18);
            --sidebar-w:264px;
            --sidebar-w-collapsed:76px;
            --topbar-h:72px;
        }

        *{box-sizing:border-box;}
        html,body{height:100%;}
        body{
            margin:0;
            font-family:'Raleway',sans-serif;
            background:var(--sand-50);
            color:var(--ink);
            -webkit-font-smoothing:antialiased;
            overflow-x:hidden;
        }
        ::selection{background:var(--terracotta-100);color:var(--coffee-900);}
        h1,h2,h3,h4{font-family:'Raleway',sans-serif;margin:0;color:var(--coffee-900);letter-spacing:-0.01em;}
        p{margin:0;}
        a{color:inherit;text-decoration:none;}
        button{font-family:inherit;cursor:pointer;}
        input,select,textarea{font-family:inherit;}
        .mono{font-family:'Raleway',sans-serif;}
        ::-webkit-scrollbar{width:9px;height:9px;}
        ::-webkit-scrollbar-track{background:transparent;}
        ::-webkit-scrollbar-thumb{background:var(--coffee-300);border-radius:10px;}
        ::-webkit-scrollbar-thumb:hover{background:var(--coffee-500);}


        /* Sidebar */
        .sidebar{
            position:fixed;top:0;left:0;bottom:0;width:var(--sidebar-w);z-index:200;
            background:var(--coffee-900);
            background-image:radial-gradient(circle at 0% 0%, rgba(212,162,76,.10), transparent 55%);
            display:flex;flex-direction:column;
            transition:width .25s ease, transform .25s ease;
            border-right:1px solid rgba(255,255,255,.06);
        }
        .sidebar.collapsed{width:var(--sidebar-w-collapsed);}
        .sb-brand{
            display:flex;align-items:center;gap:12px;padding:22px 20px;
            border-bottom:1px solid rgba(255,255,255,.08);min-height:var(--topbar-h);
        }
        .sb-mark{
            width:38px;height:38px;border-radius:10px;flex:none;
            background:linear-gradient(155deg,var(--terracotta-600),var(--gold-500));
            display:flex;align-items:center;justify-content:center;box-shadow:var(--shadow-sm);
        }
        .sb-mark svg{width:21px;height:21px;}
        .sb-brand-text{overflow:hidden;white-space:nowrap;}
        .sb-brand-text strong{display:block;color:#fff;font-family:'Raleway',sans-serif;font-size:15.5px;line-height:1.2;}
        .sb-brand-text span{display:block;color:var(--gold-500);font-size:11px;letter-spacing:.06em;text-transform:uppercase;font-weight:600;}
        .sidebar.collapsed .sb-brand-text{display:none;}
        .sb-nav{flex:1;overflow-y:auto;padding:16px 12px;}
        .sb-section-label{
            color:rgba(255,255,255,.32);font-size:10.5px;font-weight:700;letter-spacing:.09em;
            text-transform:uppercase;padding:14px 12px 8px;
        }
        .sidebar.collapsed .sb-section-label{display:none;}
        .sb-item{
            display:flex;align-items:center;gap:13px;padding:11px 12px;border-radius:10px;
            color:rgba(255,255,255,.62);font-size:14px;font-weight:500;margin-bottom:2px;
            position:relative;transition:background .15s,color .15s;white-space:nowrap;
        }
        .sb-item:hover{background:rgba(255,255,255,.06);color:#fff;}
        .sb-item.active{background:rgba(212,162,76,.16);color:var(--gold-500);}
        .sb-item.active::before{
            content:"";position:absolute;left:-12px;top:8px;bottom:8px;width:3px;border-radius:3px;
            background:var(--gold-500);
        }
        .sb-item svg{width:19px;height:19px;flex:none;}
        .sb-item .badge{
            margin-left:auto;background:var(--terracotta-600);color:#fff;font-size:11px;font-weight:700;
            padding:1px 7px;border-radius:20px;
        }
        .sidebar.collapsed .sb-item span:not(.badge){display:none;}
        .sidebar.collapsed .sb-item .badge{display:none;}
        .sidebar.collapsed .sb-item{justify-content:center;}
        .sb-drop{position:relative;}
        .sb-drop-toggle{width:100%;cursor:pointer;background:none;border:none;font-family:inherit;}
        .sb-drop-toggle .chev{margin-left:auto;opacity:.55;transition:transform .25s ease;width:15px;height:15px;flex:none;}
        .sb-drop.open .sb-drop-toggle .chev{transform:rotate(180deg);}
        .sb-drop-menu{display:none;margin:2px 0 4px;padding-left:12px;}
        .sb-drop.open .sb-drop-menu{display:block;}
        .sidebar.collapsed .sb-drop-menu{display:none;}
        .sb-drop-sub{display:flex;align-items:center;gap:9px;padding:9px 12px;border-radius:8px;margin-bottom:1px;color:rgba(255,255,255,.55);font-size:13px;font-weight:500;text-decoration:none;transition:background .15s ease,color .15s ease;}
        .sb-drop-sub:hover{color:#fff;background:rgba(255,255,255,.06);}
        .sb-drop-sub.active{color:var(--gold-500);background:rgba(212,162,76,.12);}
        .sb-drop-sub svg{width:14px;height:14px;flex:none;}
        .sb-footer{padding:14px 20px 20px;border-top:1px solid rgba(255,255,255,.08);}
        .sb-user{display:flex;align-items:center;gap:11px;}
        .sb-avatar{
            width:36px;height:36px;border-radius:50%;flex:none;
            background:linear-gradient(155deg,var(--acacia-500),var(--acacia-600));
            display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:13px;
        }
        .sb-user-text{overflow:hidden;white-space:nowrap;}
        .sb-user-text strong{display:block;color:#fff;font-size:13.5px;}
        .sb-user-text span{display:block;color:rgba(255,255,255,.5);font-size:11.5px;}
        .sidebar.collapsed .sb-user-text{display:none;}


        /* Main */
        .main{margin-left:var(--sidebar-w);transition:margin-left .25s ease;min-height:100vh;display:flex;flex-direction:column;}
        .sidebar.collapsed ~ .main{margin-left:var(--sidebar-w-collapsed);}
        .topbar{
            position:sticky;top:0;z-index:100;height:var(--topbar-h);
            background:rgba(251,247,239,.86);backdrop-filter:blur(10px);
            border-bottom:1px solid var(--line);
            display:flex;align-items:center;gap:16px;padding:0 28px;
        }
        .tb-toggle{
            width:38px;height:38px;border-radius:10px;border:1.5px solid var(--line);background:var(--white);
            display:flex;align-items:center;justify-content:center;flex:none;
        }
        .tb-toggle svg{width:18px;height:18px;color:var(--coffee-700);}
        .tb-toggle:hover{background:var(--sand-100);}
        .tb-search{
            flex:1;max-width:420px;display:flex;align-items:center;gap:10px;
            background:var(--white);border:1.5px solid var(--line);border-radius:11px;padding:9px 14px;
        }
        .tb-search svg{width:17px;height:17px;color:var(--ink-soft);flex:none;}
        .tb-search input{border:none;outline:none;background:transparent;font-size:14px;width:100%;color:var(--ink);}
        .tb-right{margin-left:auto;display:flex;align-items:center;gap:10px;}
        .tb-iconbtn{
            width:40px;height:40px;border-radius:11px;border:1.5px solid var(--line);background:var(--white);
            display:flex;align-items:center;justify-content:center;position:relative;color:var(--coffee-700);
        }
        .tb-iconbtn:hover{background:var(--sand-100);}
        .tb-iconbtn svg{width:18px;height:18px;}
        .tb-dot{
            position:absolute;top:7px;right:7px;width:8px;height:8px;border-radius:50%;
            background:var(--terracotta-600);border:2px solid var(--sand-50);
        }
        .tb-live{
            display:flex;align-items:center;gap:7px;background:var(--acacia-100);color:var(--acacia-600);
            padding:7px 13px;border-radius:20px;font-size:12.5px;font-weight:700;
        }
        .tb-live::before{
            content:"";width:7px;height:7px;border-radius:50%;background:var(--acacia-600);box-shadow:0 0 0 0 rgba(94,110,63,.5);animation:pulse 2s infinite;
        }
        @keyframes pulse{
            0%{box-shadow:0 0 0 0 rgba(94,110,63,.45);}
            70%{box-shadow:0 0 0 7px rgba(94,110,63,0);}
            100%{box-shadow:0 0 0 0 rgba(94,110,63,0);}
        }
        .view-wrap{padding:28px;flex:1;}
        .view{display:none;animation:fadeUp .35s ease;}
        .view.active{display:block;}
        @keyframes fadeUp{from{opacity:0;transform:translateY(8px);}to{opacity:1;transform:translateY(0);}}
        .view-head{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:24px;flex-wrap:wrap;}
        .view-head h2{font-size:27px;}
        .view-head .sub{color:var(--ink-soft);font-size:14px;margin-top:5px;}
        .view-actions{display:flex;gap:10px;flex-wrap:wrap;}


        /* Stats */
        .stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:24px;}
        .stat-card{
            background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);
            padding:20px 20px 18px;box-shadow:var(--shadow-sm);position:relative;overflow:hidden;
        }
        .stat-card::after{content:"";position:absolute;right:-20px;top:-20px;width:90px;height:90px;border-radius:50%;background:var(--stat-tint,var(--terracotta-100));opacity:.5;}
        .stat-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
        .stat-icon{width:40px;height:40px;border-radius:11px;display:flex;align-items:center;justify-content:center;background:var(--stat-tint,var(--terracotta-100));color:var(--stat-fg,var(--terracotta-600));position:relative;}
        .stat-icon svg{width:20px;height:20px;}
        .stat-trend{font-size:12px;font-weight:700;padding:3px 8px;border-radius:20px;}
        .stat-trend.up{color:var(--success);background:var(--acacia-100);}
        .stat-trend.down{color:var(--danger);background:var(--danger-100);}
        .stat-value{font-family:'Raleway',sans-serif;font-size:30px;color:var(--coffee-900);position:relative;}
        .stat-label{font-size:13px;color:var(--ink-soft);margin-top:4px;position:relative;}

        .panel-grid{display:grid;grid-template-columns:1.55fr 1fr;gap:18px;margin-bottom:24px;}
        .panel{background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);overflow:hidden;}
        .panel-head{display:flex;align-items:center;justify-content:space-between;padding:18px 20px 14px;border-bottom:1px solid var(--line);}
        .panel-head h3{font-size:16.5px;}
        .panel-head .link{font-size:12.5px;font-weight:700;color:var(--terracotta-600);cursor:pointer;}
        .panel-body{padding:18px 20px 20px;}

        /* Bar Chart */
        .bars{display:flex;align-items:flex-end;gap:10px;height:170px;padding-top:10px;}
        .bar-col{flex:1;display:flex;flex-direction:column;align-items:center;gap:8px;height:100%;justify-content:flex-end;}
        .bar{
            width:100%;max-width:30px;border-radius:6px 6px 2px 2px;
            background:linear-gradient(180deg,var(--terracotta-500),var(--terracotta-600));
            transition:height .6s cubic-bezier(.2,.8,.2,1);position:relative;
        }
        .bar-col:nth-child(even) .bar{background:linear-gradient(180deg,var(--gold-500),#bb8636);}
        .bar-label{font-size:11px;color:var(--ink-soft);font-weight:600;}

        .donut-wrap{display:flex;align-items:center;gap:18px;}
        .legend{display:flex;flex-direction:column;gap:10px;flex:1;}
        .legend-item{display:flex;align-items:center;gap:9px;font-size:13px;}
        .legend-dot{width:10px;height:10px;border-radius:3px;flex:none;}
        .legend-item b{margin-left:auto;color:var(--coffee-900);}

        .activity-list{display:flex;flex-direction:column;gap:3px;}
        .activity-row{display:flex;gap:12px;padding:11px 0;border-bottom:1px dashed var(--line);}
        .activity-row:last-child{border-bottom:none;}
        .activity-ico{width:34px;height:34px;border-radius:9px;flex:none;display:flex;align-items:center;justify-content:center;background:var(--sand-100);color:var(--coffee-700);}
        .activity-ico svg{width:16px;height:16px;}
        .activity-text{font-size:13.5px;line-height:1.4;}
        .activity-text b{color:var(--coffee-900);}
        .activity-time{font-size:11.5px;color:var(--ink-soft);margin-top:2px;}


        /* Tables */
        .table-card{background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);overflow:hidden;}
        .table-toolbar{display:flex;align-items:center;gap:10px;padding:16px 18px;border-bottom:1px solid var(--line);flex-wrap:wrap;}
        .chip-filters{display:flex;gap:8px;flex-wrap:wrap;}
        .chip{padding:7px 14px;border-radius:20px;font-size:12.5px;font-weight:600;background:var(--sand-100);color:var(--coffee-700);border:1px solid transparent;cursor:pointer;display:inline-flex;align-items:center;text-decoration:none;}
        .chip.active{background:var(--coffee-900);color:#fff;}
        .table-search{display:flex;align-items:center;gap:8px;background:var(--sand-50);border:1.5px solid var(--line);border-radius:10px;padding:8px 12px;margin-left:auto;min-width:200px;}
        .table-search svg{width:15px;height:15px;color:var(--ink-soft);}
        .table-search input{border:none;background:transparent;outline:none;font-size:13.5px;width:100%;}
        .table-scroll{overflow-x:auto;}
        .table-pager{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 18px;border-top:1px solid var(--line);flex-wrap:wrap;}
        .pager-pages{display:flex;gap:6px;align-items:center;flex-wrap:wrap;}
        .pager-pages a,.pager-pages span.page{min-width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;padding:0 8px;border:1px solid var(--line);border-radius:8px;font-size:12.5px;font-weight:600;color:var(--coffee-700);text-decoration:none;background:var(--white);}
        .pager-pages a:hover{border-color:var(--gold-500);color:var(--terracotta-600);}
        .pager-pages span.active{background:var(--coffee-900);color:#fff;border-color:var(--coffee-900);}
        table{width:100%;border-collapse:collapse;min-width:680px;}
        thead th{
            text-align:left;font-size:11.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--ink-soft);
            padding:12px 18px;border-bottom:1px solid var(--line);background:var(--sand-50);font-weight:700;white-space:nowrap;
        }
        tbody td{padding:14px 18px;border-bottom:1px solid var(--line);font-size:13.5px;color:var(--coffee-900);}
        tbody tr:last-child td{border-bottom:none;}
        .table-pagination{padding:16px 18px;border-top:1px solid var(--line);display:flex;align-items:center;justify-content:center;gap:8px;}
        .table-pagination a,.table-pagination span{padding:8px 14px;border-radius:8px;font-size:12.5px;font-weight:600;border:1px solid var(--line);background:var(--white);color:var(--coffee-700);text-decoration:none;transition:all .2s;}
        .table-pagination a:hover{background:var(--sand-100);border-color:var(--coffee-300);}
        .table-pagination .active{background:var(--coffee-900);color:#fff;border-color:var(--coffee-900);}
        .table-pagination .disabled{opacity:.5;cursor:not-allowed;}
        tbody tr{transition:background .12s;}
        tbody tr:hover{background:var(--sand-50);}
        .cell-main{display:flex;align-items:center;gap:11px;}
        .thumb{width:42px;height:42px;border-radius:9px;object-fit:cover;flex:none;background:var(--sand-200);}
        .cell-title{font-weight:600;color:var(--coffee-900);}
        .cell-sub{font-size:12px;color:var(--ink-soft);margin-top:1px;}
        .tag{display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:20px;font-size:11.5px;font-weight:700;white-space:nowrap;}
        .tag-green{background:var(--acacia-100);color:var(--acacia-600);}
        .tag-gold{background:var(--gold-100);color:#8a6418;}
        .tag-red{background:var(--danger-100);color:var(--danger);}
        .tag-grey{background:var(--sand-200);color:var(--ink-soft);}
        .tag-terracotta{background:var(--terracotta-100);color:var(--terracotta-600);}
        .row-actions{display:flex;gap:6px;justify-content:flex-end;}
        .row-actions button{width:32px;height:32px;border-radius:8px;border:1px solid var(--line);background:var(--white);display:flex;align-items:center;justify-content:center;color:var(--coffee-700);}
        .row-actions button:hover{background:var(--sand-100);}
        .row-actions button svg{width:14.5px;height:14.5px;}
        .row-actions .danger:hover{background:var(--danger-100);color:var(--danger);border-color:var(--danger-100);}
        .row-actions .warn:hover{background:var(--gold-100);color:#8a6418;border-color:var(--gold-100);}
        .pwd-wrap{position:relative;}
        .pwd-wrap input{padding-right:42px;}
        .pwd-toggle{position:absolute;right:4px;top:50%;transform:translateY(-50%);width:32px;height:32px;border:none;background:transparent;cursor:pointer;font-size:14px;opacity:.7;border-radius:8px;}
        .pwd-toggle:hover{opacity:1;background:var(--sand-200);}
        .input-icon-wrap{position:relative;}
        .input-icon-wrap .input-icon{position:absolute;left:14px;top:0;bottom:0;margin:auto 0;width:16px;height:16px;color:var(--coffee-300);pointer-events:none;}
        .field .input-icon-wrap input,.field .input-icon-wrap textarea{padding-left:42px;}
        .settings-section{display:flex;align-items:center;gap:9px;margin:24px 0 4px;padding-top:8px;}
        .settings-section:first-of-type{margin-top:0;padding-top:0;}
        .settings-section::before{content:'';width:22px;height:3px;border-radius:2px;background:var(--gold-500);flex:none;}
        .settings-section h4{margin:0;font-size:12.5px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--coffee-700);}
        .empty-state{padding:60px 20px;text-align:center;color:var(--ink-soft);}
        .empty-state svg{width:46px;height:46px;color:var(--coffee-300);margin-bottom:12px;}
        .empty-state h4{margin-bottom:5px;color:var(--coffee-800);}
        .empty-state p{font-size:13.5px;}


        /* Buttons */
        .btn{
            display:inline-flex;align-items:center;justify-content:center;gap:8px;
            padding:12px 20px;border-radius:var(--radius-sm);border:none;
            font-weight:600;font-size:14.5px;transition:transform .12s, box-shadow .12s, background .15s;
        }
        .btn:active{transform:translateY(1px);}
        .btn-primary{background:var(--terracotta-600);color:#fff;box-shadow:0 6px 16px rgba(194,89,43,.32);}
        .btn-primary:hover{background:var(--terracotta-500);}
        .btn-ghost{background:transparent;color:var(--coffee-700);border:1.5px solid var(--line);}
        .btn-ghost:hover{background:var(--sand-100);}
        .btn-soft{background:var(--sand-100);color:var(--coffee-800);}
        .btn-soft:hover{background:var(--sand-200);}
        .btn-danger{background:var(--danger-100);color:var(--danger);}
        .btn-danger:hover{background:#efc6c2;}
        .btn-sm{padding:8px 13px;font-size:13px;}


        /* Gallery */
        .gallery-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:16px;}
        .gallery-card{position:relative;border-radius:var(--radius-md);overflow:hidden;aspect-ratio:1/1;box-shadow:var(--shadow-sm);border:1px solid var(--line);background:var(--sand-200);}
        .gallery-card img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .35s;}
        .gallery-card:hover img{transform:scale(1.06);}
        .gallery-overlay{
            position:absolute;inset:0;background:linear-gradient(180deg,rgba(36,20,8,0) 45%,rgba(36,20,8,.82));
            display:flex;flex-direction:column;justify-content:flex-end;padding:12px;opacity:0;transition:opacity .2s;
        }
        .gallery-card:hover .gallery-overlay{opacity:1;}
        .gallery-cap{color:#fff;font-size:12.5px;font-weight:600;margin-bottom:8px;line-height:1.3;}
        .gallery-actions{display:flex;gap:6px;}
        .gallery-actions button{flex:1;padding:6px;border-radius:7px;border:none;background:rgba(255,255,255,.18);color:#fff;backdrop-filter:blur(4px);font-size:11.5px;font-weight:600;display:flex;align-items:center;justify-content:center;gap:5px;}
        .gallery-actions button:hover{background:rgba(255,255,255,.3);}
        .gallery-badge{position:absolute;top:10px;left:10px;background:rgba(36,20,8,.65);color:#fff;font-size:10.5px;font-weight:700;padding:4px 9px;border-radius:20px;text-transform:uppercase;letter-spacing:.04em;backdrop-filter:blur(4px);}
        .add-tile{
            border:2px dashed var(--coffee-300);border-radius:var(--radius-md);aspect-ratio:1/1;
            display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;color:var(--coffee-500);
            background:var(--sand-100);font-size:12.5px;font-weight:600;cursor:pointer;
        }
        .add-tile:hover{background:var(--sand-200);border-color:var(--terracotta-500);color:var(--terracotta-600);}
        .add-tile svg{width:26px;height:26px;}


        /* Reviews */
        .review-card{display:flex;gap:14px;background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);padding:18px;box-shadow:var(--shadow-sm);margin-bottom:14px;}
        .review-avatar{width:44px;height:44px;border-radius:50%;flex:none;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:15px;background:linear-gradient(155deg,var(--terracotta-600),var(--gold-500));}
        .review-top{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:4px;flex-wrap:wrap;}
        .review-name{font-weight:700;color:var(--coffee-900);font-size:14.5px;}
        .review-tour{font-size:12px;color:var(--ink-soft);margin-top:1px;}
        .review-text{font-size:13.8px;color:var(--coffee-800);line-height:1.55;margin:8px 0 10px;}
        .review-actions{display:flex;gap:8px;}


        /* Messages */
        .msg-layout{display:grid;grid-template-columns:340px 1fr;gap:18px;align-items:start;}
        .msg-list{background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);overflow:hidden;box-shadow:var(--shadow-sm);}
        .msg-item{display:flex;gap:11px;padding:14px 16px;border-bottom:1px solid var(--line);cursor:pointer;position:relative;}
        .msg-item:hover{background:var(--sand-50);}
        .msg-item.active{background:var(--terracotta-100);}
        .msg-item.unread::before{content:"";position:absolute;left:6px;top:50%;transform:translateY(-50%);width:7px;height:7px;border-radius:50%;background:var(--terracotta-600);}
        .msg-item-name{font-weight:700;font-size:13.5px;color:var(--coffee-900);}
        .msg-item-prev{font-size:12px;color:var(--ink-soft);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:230px;}
        .msg-item-time{font-size:10.5px;color:var(--ink-soft);margin-left:auto;flex:none;}
        .msg-detail{background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);padding:24px;min-height:420px;display:flex;flex-direction:column;}
        .msg-detail-head{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1px solid var(--line);padding-bottom:16px;margin-bottom:16px;}
        .msg-detail-body{font-size:14.5px;line-height:1.7;color:var(--coffee-800);flex:1;}
        .msg-detail-foot{display:flex;gap:10px;margin-top:20px;padding-top:16px;border-top:1px solid var(--line);}


        /* Settings */
        .settings-grid{display:grid;grid-template-columns:240px 1fr;gap:24px;align-items:start;}
        .settings-grid-single{grid-template-columns:1fr;}
        .settings-nav{display:flex;flex-direction:column;gap:3px;background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);padding:10px;box-shadow:var(--shadow-sm);}
        .settings-nav button{
            display:flex;align-items:center;gap:10px;text-align:left;padding:11px 13px;border-radius:9px;border:none;background:transparent;
            font-size:13.8px;font-weight:600;color:var(--coffee-700);cursor:pointer;
        }
        .settings-nav button.active{background:var(--sand-100);color:var(--terracotta-600);}
        .settings-nav button svg{width:17px;height:17px;}
        .settings-panel{background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);padding:26px;}
        .settings-panel h3{font-size:17px;margin-bottom:18px;}
        .settings-pane{display:none;}
        .settings-pane.active{display:block;}
        .field{margin-bottom:16px;}
        .field label{display:block;font-size:12.5px;font-weight:600;color:var(--coffee-700);margin-bottom:7px;text-transform:uppercase;letter-spacing:.04em;}
        .field input,.field select,.field textarea{
            width:100%;padding:12px 14px;border:1.5px solid var(--line);border-radius:var(--radius-sm);
            background:var(--white);font-size:14.5px;color:var(--ink);transition:border-color .15s, box-shadow .15s;
        }
        .field input:focus,.field select:focus,.field textarea:focus{
            outline:none;border-color:var(--terracotta-500);box-shadow:0 0 0 3px var(--terracotta-100);
        }
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
        .toggle-row{display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid var(--line);}
        .toggle-row:last-child{border-bottom:none;}
        .toggle-text strong{display:block;font-size:14px;color:var(--coffee-900);margin-bottom:2px;}
        .toggle-text span{font-size:12.5px;color:var(--ink-soft);}
        .switch{width:42px;height:24px;border-radius:20px;background:var(--sand-200);position:relative;flex:none;border:none;cursor:pointer;transition:background .2s;}
        .switch::after{content:"";position:absolute;top:3px;left:3px;width:18px;height:18px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.25);transition:transform .2s;}
        .switch.on{background:var(--acacia-500);}
        .switch.on::after{transform:translateX(18px);}


        /* Modal */
        .modal-backdrop{
            position:fixed;inset:0;background:rgba(36,20,8,.5);backdrop-filter:blur(2px);
            display:none;align-items:flex-start;justify-content:center;z-index:400;padding:40px 20px;overflow-y:auto;
        }
        .modal-backdrop.show{display:flex;}
        .modal{
            background:var(--sand-50);border-radius:var(--radius-lg);width:100%;max-width:560px;
            box-shadow:var(--shadow-lg);animation:riseIn .3s cubic-bezier(.2,.8,.2,1);margin:auto;
        }
        @keyframes riseIn{from{opacity:0;transform:translateY(14px);}to{opacity:1;transform:translateY(0);}}
        .modal-head{display:flex;align-items:center;justify-content:space-between;padding:22px 24px;border-bottom:1px solid var(--line);}
        .modal-head h3{font-size:19px;}
        .modal-close{width:34px;height:34px;border-radius:9px;border:1px solid var(--line);background:var(--white);display:flex;align-items:center;justify-content:center;}
        .modal-body{padding:22px 24px;max-height:60vh;overflow-y:auto;}
        .modal-foot{display:flex;justify-content:flex-end;gap:10px;padding:18px 24px;border-top:1px solid var(--line);}


        /* Toast */
        #toastHost{position:fixed;bottom:24px;right:24px;z-index:600;display:flex;flex-direction:column;gap:10px;}
        .toast{
            background:var(--coffee-900);color:#fff;padding:13px 18px;border-radius:11px;font-size:13.5px;font-weight:600;
            box-shadow:var(--shadow-lg);display:flex;align-items:center;gap:10px;min-width:240px;animation:toastIn .3s ease;
        }
        .toast.success{background:var(--acacia-600);}
        .toast.error{background:var(--danger);}
        .toast svg{width:17px;height:17px;flex:none;}
        @keyframes toastIn{from{opacity:0;transform:translateX(20px);}to{opacity:1;transform:translateX(0);}}


        /* Responsive */
        .mobile-overlay{position:fixed;inset:0;background:rgba(36,20,8,.45);z-index:190;display:none;}
        .mobile-overlay.show{display:block;}
        @media (max-width:1180px){
            .stat-grid{grid-template-columns:repeat(2,1fr);}
            .panel-grid{grid-template-columns:1fr;}
            .settings-grid{grid-template-columns:1fr;}
            .msg-layout{grid-template-columns:1fr;}
        }
        @media (max-width:900px){
            .sidebar{transform:translateX(-100%);width:var(--sidebar-w);z-index:300;}
            .sidebar.mobile-open{transform:translateX(0);}
            .main{margin-left:0 !important;}
            .tb-search{display:none;}
        }
        @media (max-width:640px){
            .stat-grid{grid-template-columns:1fr;}
            .view-wrap{padding:16px;}
            .topbar{padding:0 14px;gap:10px;}
            .form-row{grid-template-columns:1fr;}
            .tb-live span{display:none;}
            .view-head h2{font-size:22px;}
        }
        @media (prefers-reduced-motion:reduce){
            *{animation-duration:.001ms !important;transition-duration:.001ms !important;}
        }
    </style>
</head>
<body>
    <div id="app" class="show">
        <div class="mobile-overlay" id="mobileOverlay" onclick="closeMobileSidebar()"></div>
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sb-brand">
                <img src="https://res.cloudinary.com/aenplcpl/image/upload/f_auto,q_auto,w_200/v1782890324/safari-logo-white_bexcal.png" alt="Tanzania Daily Tours & Safari" style="width: 38px; height: 38px; border-radius: 10px; flex: none;">
                <div class="sb-brand-text">
                    <strong>Tanzania Daily</strong>
                    <span>Tours & Safari · CMS</span>
                </div>
            </div>
            <nav class="sb-nav">
                <div class="sb-section-label">Overview</div>
                <a href="https://tanzaniadailytoursandsafari.com/live" class="sb-item active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="9" rx="1.5"></rect>
                        <rect x="14" y="3" width="7" height="5" rx="1.5"></rect>
                        <rect x="14" y="12" width="7" height="9" rx="1.5"></rect>
                        <rect x="3" y="16" width="7" height="5" rx="1.5"></rect>
                    </svg>
                    <span>Dashboard</span>
                </a>
                <div class="sb-section-label">Content</div>
                <a href="https://tanzaniadailytoursandsafari.com/live/destinations" class="sb-item ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 11 12 4l9 7"></path>
                        <path d="M5 10v10h14V10"></path>
                        <path d="M9 20v-6h6v6"></path>
                    </svg>
                    <span>Destinations</span>
                    <span class="badge" id="navDestCount">62</span>
                </a>
                <a href="https://tanzaniadailytoursandsafari.com/live/gallery" class="sb-item ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.8"></circle>
                        <path d="m21 15-5-5L5 21"></path>
                    </svg>
                    <span>Gallery</span>
                </a>
                <a href="https://tanzaniadailytoursandsafari.com/live/reviews" class="sb-item ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2.5 15.1 9 22 10 17 15 18.2 22 12 18.6 5.8 22 7 15 2 10 8.9 9"></polygon>
                    </svg>
                    <span>Reviews</span>
                    <span class="badge" id="navReviewCount">0</span>
                </a>
                <div class="sb-section-label">Operations</div>
                <a href="https://tanzaniadailytoursandsafari.com/live/bookings" class="sb-item ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="17" rx="2"></rect>
                        <path d="M16 2v4M8 2v4M3 9h18"></path>
                        <path d="m9 14 2 2 4-4"></path>
                    </svg>
                    <span>Bookings</span>
                    <span class="badge" id="navBookingCount">1</span>
                </a>
                <a href="https://tanzaniadailytoursandsafari.com/live/payments" class="sb-item ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                        <line x1="2" y1="10" x2="22" y2="10"></line>
                    </svg>
                    <span>Payments</span>
                    <span class="badge" id="navPaymentCount">5</span>
                </a>
                <a href="https://tanzaniadailytoursandsafari.com/live/messages" class="sb-item ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2Z"></path>
                    </svg>
                    <span>Messages</span>
                    <span class="badge" id="navMsgCount">0</span>
                </a>
                <div class="sb-section-label">System</div>
                <div class="sb-drop ">
                    <button type="button" class="sb-item sb-drop-toggle " onclick="toggleSbDrop(this)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1.04 1.56V21a2 2 0 0 1-4 0v-.09A1.7 1.7 0 0 0 9 19.4a1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.56-1.04H3a2 2 0 0 1 0-4h.09A1.7 1.7 0 0 0 4.6 9a1.7 1.7 0 0 0-.34-1.87l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1.04-1.56V3a2 2 0 0 1 4 0v.09A1.7 1.7 0 0 0 15 4.6a1.7 1.7 0 0 0 1.87-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9a1.7 1.7 0 0 0 1.56 1.04H21a2 2 0 0 1 0 4h-.09A1.7 1.7 0 0 0 19.4 15Z"></path>
                        </svg>
                        <span>Site Settings</span>
                        <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <div class="sb-drop-menu">
                        <a href="https://tanzaniadailytoursandsafari.com/live/settings?pane=general" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                                <rect x="9" y="9" width="6" height="6"></rect>
                            </svg>
                            General
                        </a>
                        <a href="https://tanzaniadailytoursandsafari.com/live/settings?pane=brand" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="13.5" cy="6.5" r=".5"></circle>
                                <circle cx="17.5" cy="10.5" r=".5"></circle>
                                <circle cx="8.5" cy="7.5" r=".5"></circle>
                                <circle cx="6.5" cy="12.5" r=".5"></circle>
                                <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C22 6.012 17.461 2 12 2Z"></path>
                            </svg>
                            Brand & Colors
                        </a>
                        <a href="https://tanzaniadailytoursandsafari.com/live/settings?pane=contact" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92Z"></path>
                            </svg>
                            Contact & Social
                        </a>
                        <a href="https://tanzaniadailytoursandsafari.com/live/settings?pane=notifications" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path>
                                <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path>
                            </svg>
                            Notifications
                        </a>
                        <a href="https://tanzaniadailytoursandsafari.com/live/users" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            Admin Users
                        </a>
                        <a href="https://tanzaniadailytoursandsafari.com/live/settings?pane=payments" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                                <line x1="2" y1="10" x2="22" y2="10"></line>
                            </svg>
                            Payments
                        </a>
                        <a href="https://tanzaniadailytoursandsafari.com/live/settings?pane=mail" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                            </svg>
                            Mail
                        </a>
                    </div>
                </div>
            </nav>
            <div class="sb-footer">
                <a href="https://tanzaniadailytoursandsafari.com/live/profile" class="sb-user" style="text-decoration:none;">
                    <div class="sb-avatar">
                        JE
                    </div>
                    <div class="sb-user-text">
                        <strong>Jeremia Developer</strong>
                        <span>jeremiat449@gmail.com</span>
                    </div>
                </a>
            </div>
        </aside>

        <!-- Main -->
        <div class="main" id="mainArea">
            <header class="topbar">
                <button class="tb-toggle" onclick="toggleSidebar()" aria-label="Toggle sidebar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="4" y1="7" x2="20" y2="7"></line>
                        <line x1="4" y1="12" x2="20" y2="12"></line>
                        <line x1="4" y1="17" x2="20" y2="17"></line>
                    </svg>
                </button>
                <div class="tb-search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" placeholder="Search tours, bookings, guests…">
                </div>
                <div class="tb-right">
                    <div class="tb-live"><span>Site live</span></div>
                    <a class="tb-iconbtn" href="https://tanzaniadailytoursandsafari.com" target="_blank" rel="noopener" aria-label="View live site" title="View live site">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                            <path d="M15 3h6v6"></path>
                            <path d="M10 14 21 3"></path>
                        </svg>
                    </a>
                    <form method="POST" action="https://tanzaniadailytoursandsafari.com/live/logout" style="display:inline;">
                        <input type="hidden" name="_token" value="w3Vlfvw2Bh8VfqolGpMryy5mbgT3npZg5cr409mV" autocomplete="off">                        <button type="submit" class="tb-iconbtn" aria-label="Log out" title="Log out">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16 17 21 12 16 7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                        </button>
                    </form>
                </div>
            </header>

            <div class="view-wrap">
                <!-- Session Messages -->
                
                <!-- Content -->
                <div class="view active">
    <div class="view-head">
        <div>
            <h2>Welcome back, Admin 🌍</h2>
            <p class="sub">Here's what's happening across Tanzania Daily Tours & Safari today.</p>
        </div>
        <div class="view-actions" style="gap: 12px;">
            <div class="field" style="margin-bottom: 0;">
                <select id="currencySwitcher" onchange="updateCurrencyDisplay(this.value)" style="min-width: 120px;">
                                        <option value="USD" selected>USD</option>
                                        <option value="EUR" >EUR</option>
                                        <option value="GBP" >GBP</option>
                                        <option value="JPY" >JPY</option>
                                        <option value="CAD" >CAD</option>
                                        <option value="AUD" >AUD</option>
                                        <option value="INR" >INR</option>
                                        <option value="TZS" >TZS</option>
                                        <option value="KES" >KES</option>
                                        <option value="UGX" >UGX</option>
                                        <option value="ZAR" >ZAR</option>
                                    </select>
            </div>
            <a href="https://tanzaniadailytoursandsafari.com/live/bookings" class="btn btn-soft">View bookings</a>
            <a href="https://tanzaniadailytoursandsafari.com/live/destinations" class="btn btn-primary">+ Add destination</a>
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat-card" style="--stat-tint: var(--terracotta-100); --stat-fg: var(--terracotta-600);">
            <div class="stat-top">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="17" rx="2"></rect>
                        <path d="M16 2v4M8 2v4M3 9h18"></path>
                    </svg>
                </div>
                <span class="stat-trend up">+12.4%</span>
            </div>
            <div class="stat-value" id="statBookings">2</div>
            <div class="stat-label">Total bookings</div>
        </div>
        <div class="stat-card" style="--stat-tint: var(--acacia-100); --stat-fg: var(--acacia-600);">
            <div class="stat-top">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                </div>
                <span class="stat-trend up">+8.1%</span>
            </div>
            <div class="stat-value" id="statRevenue" data-revenue-usd="1940">$1,940</div>
            <div class="stat-label">Revenue (this month)</div>
        </div>
        <div class="stat-card" style="--stat-tint: var(--gold-100); --stat-fg: #8a6418;">
            <div class="stat-top">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 11 12 4l9 7"></path>
                        <path d="M5 10v10h14V10"></path>
                    </svg>
                </div>
                <span class="stat-trend up">+62</span>
            </div>
            <div class="stat-value" id="statDestinations">62</div>
            <div class="stat-label">Active destinations</div>
        </div>
        <div class="stat-card" style="--stat-tint: var(--danger-100); --stat-fg: var(--danger);">
            <div class="stat-top">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2Z"></path>
                    </svg>
                </div>
                <span class="stat-trend down">New</span>
            </div>
            <div class="stat-value" id="statMessages">0</div>
            <div class="stat-label">Unread messages</div>
        </div>
    </div>

    <div class="panel-grid">
        <div class="panel">
            <div class="panel-head">
                <h3>Bookings — last 7 months</h3>
                <span class="link">+ 18% vs prior period</span>
            </div>
            <div class="panel-body">
                <div class="bars" id="bookingsChart">
                    <div class="bar-col">
                        <div class="bar" style="height: 60%;"></div>
                        <div class="bar-label">Jan</div>
                    </div>
                    <div class="bar-col">
                        <div class="bar" style="height: 70%;"></div>
                        <div class="bar-label">Feb</div>
                    </div>
                    <div class="bar-col">
                        <div class="bar" style="height: 50%;"></div>
                        <div class="bar-label">Mar</div>
                    </div>
                    <div class="bar-col">
                        <div class="bar" style="height: 80%;"></div>
                        <div class="bar-label">Apr</div>
                    </div>
                    <div class="bar-col">
                        <div class="bar" style="height: 75%;"></div>
                        <div class="bar-label">May</div>
                    </div>
                    <div class="bar-col">
                        <div class="bar" style="height: 90%;"></div>
                        <div class="bar-label">Jun</div>
                    </div>
                    <div class="bar-col">
                        <div class="bar" style="height: 50%;"></div>
                        <div class="bar-label">Jul</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="panel">
            <div class="panel-head">
                <h3>Bookings by category</h3>
            </div>
            <div class="panel-body">
                <div class="donut-wrap">
                    <svg width="120" height="120" viewBox="0 0 120 120" id="donutChart">
                        <circle cx="60" cy="60" r="50" fill="none" stroke="#E9DCC0" stroke-width="14"/>
                    </svg>
                    <div class="legend" id="donutLegend">
                        <div class="legend-item">
                            <span class="legend-dot" style="background: #C2592B;"></span>
                            Day Trips
                            <b>1</b>
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot" style="background: #D4A24C;"></span>
                            Multi-Day Safaris
                            <b>1</b>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="panel-grid">
        <div class="panel">
            <div class="panel-head">
                <h3>Payments</h3>
                <a href="https://tanzaniadailytoursandsafari.com/live/payments" class="link">View all →</a>
            </div>
            <div class="panel-body">
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:18px;" class="payment-mini">
                    <div style="background:var(--acacia-100);border-radius:12px;padding:16px;">
                        <div style="font-size:12px;color:var(--acacia-600);font-weight:700;">Collected</div>
                        <div style="font-size:22px;font-weight:800;color:var(--coffee-900);margin-top:4px;">$0</div>
                    </div>
                    <div style="background:var(--gold-100);border-radius:12px;padding:16px;">
                        <div style="font-size:12px;color:#8a6418;font-weight:700;">Pending (5)</div>
                        <div style="font-size:22px;font-weight:800;color:var(--coffee-900);margin-top:4px;">$1,403,549</div>
                    </div>
                    <div style="background:var(--sand-100);border-radius:12px;padding:16px;">
                        <div style="font-size:12px;color:var(--ink-soft);font-weight:700;">Fee</div>
                        <div style="font-size:13px;font-weight:600;color:var(--coffee-700);margin-top:8px;line-height:1.5;">PesaPal charges a small %<br>on completed payments</div>
                    </div>
                </div>
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                                                                                    <tr>
                                <td class="cell-title"><a href="https://tanzaniadailytoursandsafari.com/live/payments/7">TDTS-38A06363</a></td>
                                <td>davidngungila@gmail.com</td>
                                <td>TSh68,580</td>
                                <td><span class="tag tag-gold">Pending</span></td>
                            </tr>
                                                        <tr>
                                <td class="cell-title"><a href="https://tanzaniadailytoursandsafari.com/live/payments/6">TDTS-670195B3</a></td>
                                <td>jeremiat449@gmail.com</td>
                                <td>$585</td>
                                <td><span class="tag tag-gold">Pending</span></td>
                            </tr>
                                                        <tr>
                                <td class="cell-title"><a href="https://tanzaniadailytoursandsafari.com/live/payments/5">TDTS-B3030CFA</a></td>
                                <td>ally0623975@gmail.com</td>
                                <td>$2,687</td>
                                <td><span class="tag tag-red">Failed</span></td>
                            </tr>
                                                        <tr>
                                <td class="cell-title"><a href="https://tanzaniadailytoursandsafari.com/live/payments/4">TDTS-B70B325B</a></td>
                                <td>jeremiat449@gmail.com</td>
                                <td>$585</td>
                                <td><span class="tag tag-red">Failed</span></td>
                            </tr>
                                                        <tr>
                                <td class="cell-title"><a href="https://tanzaniadailytoursandsafari.com/live/payments/3">TDTS-9F87BB77</a></td>
                                <td>jeremiat449@gmail.com</td>
                                <td>$585</td>
                                <td><span class="tag tag-gold">Pending</span></td>
                            </tr>
                                                                                </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="panel">
            <div class="panel-head">
                <h3>Recent bookings</h3>
                <a href="https://tanzaniadailytoursandsafari.com/live/bookings" class="link">View all →</a>
            </div>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Guest</th>
                            <th>Tour</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="recentBookingsBody">
                                                                        <tr>
                            <td class="cell-title">David Ngungila</td>
                            <td>Chemka Hot Springs</td>
                            <td>Sep 14, 2026</td>
                            <td><span class="tag tag-gold">Pending</span></td>
                        </tr>
                                                <tr>
                            <td class="cell-title">Epimack Laurent</td>
                            <td>4-Day Gombe Chimpanzee Trekking Safari from Dar es Salaam</td>
                            <td>Sep 03, 2026</td>
                            <td><span class="tag tag-green">Confirmed</span></td>
                        </tr>
                                                                    </tbody>
                </table>
            </div>
        </div>
        <div class="panel">
            <div class="panel-head">
                <h3>Recent activity</h3>
            </div>
            <div class="panel-body">
                <div class="activity-list" id="activityList">
                    <div class="activity-row">
                        <div class="activity-ico">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="17" rx="2"></rect>
                                <path d="M16 2v4M8 2v4M3 9h18"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="activity-text"><b>Guest</b> booked a tour</div>
                            <div class="activity-time">2 hours ago</div>
                        </div>
                    </div>
                                                            <div class="activity-row">
                        <div class="activity-ico">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 11 12 4l9 7"></path>
                                <path d="M5 10v10h14V10"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="activity-text">Destination updated</div>
                            <div class="activity-time">Yesterday</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const exchangeRates = {"USD":1,"EUR":0.92,"GBP":0.79,"JPY":151,"CAD":1.36,"AUD":1.53,"INR":83,"TZS":2540,"KES":130.5,"UGX":3800,"ZAR":18.5};
const currencySymbols = {"USD":"$","EUR":"\u20ac","GBP":"\u00a3","JPY":"\u00a5","CAD":"C$","AUD":"A$","INR":"\u20b9","TZS":"TSh","KES":"KSh","UGX":"USh","ZAR":"R"};

function updateCurrencyDisplay(currency) {
    // Save to session via AJAX
    fetch("/live/currency-switch", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ currency: currency })
    });

    // Update revenue
    const statRevenue = document.getElementById("statRevenue");
    const revenueUSD = parseFloat(statRevenue.dataset.revenueUsd);
    const converted = revenueUSD * exchangeRates[currency];
    statRevenue.textContent = formatCurrency(converted, currency);
}

function formatCurrency(amount, currency) {
    const symbol = currencySymbols[currency] || '$';
    return symbol + amount.toLocaleString('en-US', { maximumFractionDigits: 0 });
}
</script>
            </div>
        </div>
    </div>
    <div id="toastHost"></div>
    <script>
        // Auto-logout timer (5 minutes in milliseconds)
        const AUTO_LOGOUT_TIME = 5 * 60 * 1000;
        let autoLogoutTimer;

        // Reset the auto-logout timer on user activity
        function resetAutoLogoutTimer() {
            clearTimeout(autoLogoutTimer);
            autoLogoutTimer = setTimeout(() => {
                // Clear the session on the server side by redirecting to login
                window.location.href = "https://tanzaniadailytoursandsafari.com/live/login";
            }, AUTO_LOGOUT_TIME);
        }

        // Add event listeners for user activity
        ['click', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
            document.addEventListener(event, resetAutoLogoutTimer, true);
        });

        // Initialize the timer when the page loads
        resetAutoLogoutTimer();

        function toggleSidebar(){
            if(window.innerWidth <= 900){
                document.getElementById('sidebar').classList.toggle('mobile-open');
                document.getElementById('mobileOverlay').classList.toggle('show');
            } else {
                document.getElementById('sidebar').classList.toggle('collapsed');
            }
        }
        function closeMobileSidebar(){
            document.getElementById('sidebar').classList.remove('mobile-open');
            document.getElementById('mobileOverlay').classList.remove('show');
        }
        function toggleSbDrop(el){
            const drop = el.closest('.sb-drop');
            const wasOpen = drop.classList.contains('open');
            document.querySelectorAll('.sb-drop').forEach(d => d.classList.remove('open'));
            if(!wasOpen) drop.classList.add('open');
        }

        function toast(msg, type='default'){
            const host = document.getElementById('toastHost');
            const el = document.createElement('div');
            el.className = 'toast ' + (type==='success'?'success':type==='error'?'error':'');
            el.innerHTML = (type==='success' ? '<svg viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><path d=\"M20 6 9 17l-5-5\"></path></svg>' : '') + '<span>'+msg+'</span>';
            host.appendChild(el);
            setTimeout(()=>{ el.style.opacity='0'; el.style.transform='translateX(20px)'; el.style.transition='all .25s'; setTimeout(()=>el.remove(),250); }, 3000);
        }

        function openModal(id){ document.getElementById(id).classList.add('show'); }
        function closeModal(id){ document.getElementById(id).classList.remove('show'); }
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="w3Vlfvw2Bh8VfqolGpMryy5mbgT3npZg5cr409mV">
    <title>Reviews · Tanzania Daily Tours & Safari</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        :root{
            --sand-50:#FBF7EF;
            --sand-100:#F4ECDC;
            --sand-200:#E9DCC0;
            --coffee-900:#2A1B10;
            --coffee-800:#3B2718;
            --coffee-700:#4D3422;
            --coffee-500:#7A5C42;
            --coffee-300:#A98968;
            --terracotta-600:#C2592B;
            --terracotta-500:#D06B3A;
            --terracotta-100:#F6E1D3;
            --acacia-600:#5E6E3F;
            --acacia-500:#7A8450;
            --acacia-100:#E2E7D4;
            --gold-500:#D4A24C;
            --gold-100:#F7E9CB;
            --ink:#241408;
            --ink-soft:#6B5A48;
            --line:#E4D7C2;
            --white:#FFFFFF;
            --danger:#B33A3A;
            --danger-100:#F6DCDA;
            --success:#3F6B3F;
            --radius-sm:8px;
            --radius-md:14px;
            --radius-lg:20px;
            --shadow-sm:0 1px 2px rgba(42,27,16,.08);
            --shadow-md:0 8px 24px rgba(42,27,16,.10);
            --shadow-lg:0 20px 48px rgba(42,27,16,.18);
            --sidebar-w:264px;
            --sidebar-w-collapsed:76px;
            --topbar-h:72px;
        }

        *{box-sizing:border-box;}
        html,body{height:100%;}
        body{
            margin:0;
            font-family:'Raleway',sans-serif;
            background:var(--sand-50);
            color:var(--ink);
            -webkit-font-smoothing:antialiased;
            overflow-x:hidden;
        }
        ::selection{background:var(--terracotta-100);color:var(--coffee-900);}
        h1,h2,h3,h4{font-family:'Raleway',sans-serif;margin:0;color:var(--coffee-900);letter-spacing:-0.01em;}
        p{margin:0;}
        a{color:inherit;text-decoration:none;}
        button{font-family:inherit;cursor:pointer;}
        input,select,textarea{font-family:inherit;}
        .mono{font-family:'Raleway',sans-serif;}
        ::-webkit-scrollbar{width:9px;height:9px;}
        ::-webkit-scrollbar-track{background:transparent;}
        ::-webkit-scrollbar-thumb{background:var(--coffee-300);border-radius:10px;}
        ::-webkit-scrollbar-thumb:hover{background:var(--coffee-500);}


        /* Sidebar */
        .sidebar{
            position:fixed;top:0;left:0;bottom:0;width:var(--sidebar-w);z-index:200;
            background:var(--coffee-900);
            background-image:radial-gradient(circle at 0% 0%, rgba(212,162,76,.10), transparent 55%);
            display:flex;flex-direction:column;
            transition:width .25s ease, transform .25s ease;
            border-right:1px solid rgba(255,255,255,.06);
        }
        .sidebar.collapsed{width:var(--sidebar-w-collapsed);}
        .sb-brand{
            display:flex;align-items:center;gap:12px;padding:22px 20px;
            border-bottom:1px solid rgba(255,255,255,.08);min-height:var(--topbar-h);
        }
        .sb-mark{
            width:38px;height:38px;border-radius:10px;flex:none;
            background:linear-gradient(155deg,var(--terracotta-600),var(--gold-500));
            display:flex;align-items:center;justify-content:center;box-shadow:var(--shadow-sm);
        }
        .sb-mark svg{width:21px;height:21px;}
        .sb-brand-text{overflow:hidden;white-space:nowrap;}
        .sb-brand-text strong{display:block;color:#fff;font-family:'Raleway',sans-serif;font-size:15.5px;line-height:1.2;}
        .sb-brand-text span{display:block;color:var(--gold-500);font-size:11px;letter-spacing:.06em;text-transform:uppercase;font-weight:600;}
        .sidebar.collapsed .sb-brand-text{display:none;}
        .sb-nav{flex:1;overflow-y:auto;padding:16px 12px;}
        .sb-section-label{
            color:rgba(255,255,255,.32);font-size:10.5px;font-weight:700;letter-spacing:.09em;
            text-transform:uppercase;padding:14px 12px 8px;
        }
        .sidebar.collapsed .sb-section-label{display:none;}
        .sb-item{
            display:flex;align-items:center;gap:13px;padding:11px 12px;border-radius:10px;
            color:rgba(255,255,255,.62);font-size:14px;font-weight:500;margin-bottom:2px;
            position:relative;transition:background .15s,color .15s;white-space:nowrap;
        }
        .sb-item:hover{background:rgba(255,255,255,.06);color:#fff;}
        .sb-item.active{background:rgba(212,162,76,.16);color:var(--gold-500);}
        .sb-item.active::before{
            content:"";position:absolute;left:-12px;top:8px;bottom:8px;width:3px;border-radius:3px;
            background:var(--gold-500);
        }
        .sb-item svg{width:19px;height:19px;flex:none;}
        .sb-item .badge{
            margin-left:auto;background:var(--terracotta-600);color:#fff;font-size:11px;font-weight:700;
            padding:1px 7px;border-radius:20px;
        }
        .sidebar.collapsed .sb-item span:not(.badge){display:none;}
        .sidebar.collapsed .sb-item .badge{display:none;}
        .sidebar.collapsed .sb-item{justify-content:center;}
        .sb-drop{position:relative;}
        .sb-drop-toggle{width:100%;cursor:pointer;background:none;border:none;font-family:inherit;}
        .sb-drop-toggle .chev{margin-left:auto;opacity:.55;transition:transform .25s ease;width:15px;height:15px;flex:none;}
        .sb-drop.open .sb-drop-toggle .chev{transform:rotate(180deg);}
        .sb-drop-menu{display:none;margin:2px 0 4px;padding-left:12px;}
        .sb-drop.open .sb-drop-menu{display:block;}
        .sidebar.collapsed .sb-drop-menu{display:none;}
        .sb-drop-sub{display:flex;align-items:center;gap:9px;padding:9px 12px;border-radius:8px;margin-bottom:1px;color:rgba(255,255,255,.55);font-size:13px;font-weight:500;text-decoration:none;transition:background .15s ease,color .15s ease;}
        .sb-drop-sub:hover{color:#fff;background:rgba(255,255,255,.06);}
        .sb-drop-sub.active{color:var(--gold-500);background:rgba(212,162,76,.12);}
        .sb-drop-sub svg{width:14px;height:14px;flex:none;}
        .sb-footer{padding:14px 20px 20px;border-top:1px solid rgba(255,255,255,.08);}
        .sb-user{display:flex;align-items:center;gap:11px;}
        .sb-avatar{
            width:36px;height:36px;border-radius:50%;flex:none;
            background:linear-gradient(155deg,var(--acacia-500),var(--acacia-600));
            display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:13px;
        }
        .sb-user-text{overflow:hidden;white-space:nowrap;}
        .sb-user-text strong{display:block;color:#fff;font-size:13.5px;}
        .sb-user-text span{display:block;color:rgba(255,255,255,.5);font-size:11.5px;}
        .sidebar.collapsed .sb-user-text{display:none;}


        /* Main */
        .main{margin-left:var(--sidebar-w);transition:margin-left .25s ease;min-height:100vh;display:flex;flex-direction:column;}
        .sidebar.collapsed ~ .main{margin-left:var(--sidebar-w-collapsed);}
        .topbar{
            position:sticky;top:0;z-index:100;height:var(--topbar-h);
            background:rgba(251,247,239,.86);backdrop-filter:blur(10px);
            border-bottom:1px solid var(--line);
            display:flex;align-items:center;gap:16px;padding:0 28px;
        }
        .tb-toggle{
            width:38px;height:38px;border-radius:10px;border:1.5px solid var(--line);background:var(--white);
            display:flex;align-items:center;justify-content:center;flex:none;
        }
        .tb-toggle svg{width:18px;height:18px;color:var(--coffee-700);}
        .tb-toggle:hover{background:var(--sand-100);}
        .tb-search{
            flex:1;max-width:420px;display:flex;align-items:center;gap:10px;
            background:var(--white);border:1.5px solid var(--line);border-radius:11px;padding:9px 14px;
        }
        .tb-search svg{width:17px;height:17px;color:var(--ink-soft);flex:none;}
        .tb-search input{border:none;outline:none;background:transparent;font-size:14px;width:100%;color:var(--ink);}
        .tb-right{margin-left:auto;display:flex;align-items:center;gap:10px;}
        .tb-iconbtn{
            width:40px;height:40px;border-radius:11px;border:1.5px solid var(--line);background:var(--white);
            display:flex;align-items:center;justify-content:center;position:relative;color:var(--coffee-700);
        }
        .tb-iconbtn:hover{background:var(--sand-100);}
        .tb-iconbtn svg{width:18px;height:18px;}
        .tb-dot{
            position:absolute;top:7px;right:7px;width:8px;height:8px;border-radius:50%;
            background:var(--terracotta-600);border:2px solid var(--sand-50);
        }
        .tb-live{
            display:flex;align-items:center;gap:7px;background:var(--acacia-100);color:var(--acacia-600);
            padding:7px 13px;border-radius:20px;font-size:12.5px;font-weight:700;
        }
        .tb-live::before{
            content:"";width:7px;height:7px;border-radius:50%;background:var(--acacia-600);box-shadow:0 0 0 0 rgba(94,110,63,.5);animation:pulse 2s infinite;
        }
        @keyframes pulse{
            0%{box-shadow:0 0 0 0 rgba(94,110,63,.45);}
            70%{box-shadow:0 0 0 7px rgba(94,110,63,0);}
            100%{box-shadow:0 0 0 0 rgba(94,110,63,0);}
        }
        .view-wrap{padding:28px;flex:1;}
        .view{display:none;animation:fadeUp .35s ease;}
        .view.active{display:block;}
        @keyframes fadeUp{from{opacity:0;transform:translateY(8px);}to{opacity:1;transform:translateY(0);}}
        .view-head{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:24px;flex-wrap:wrap;}
        .view-head h2{font-size:27px;}
        .view-head .sub{color:var(--ink-soft);font-size:14px;margin-top:5px;}
        .view-actions{display:flex;gap:10px;flex-wrap:wrap;}


        /* Stats */
        .stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:24px;}
        .stat-card{
            background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);
            padding:20px 20px 18px;box-shadow:var(--shadow-sm);position:relative;overflow:hidden;
        }
        .stat-card::after{content:"";position:absolute;right:-20px;top:-20px;width:90px;height:90px;border-radius:50%;background:var(--stat-tint,var(--terracotta-100));opacity:.5;}
        .stat-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
        .stat-icon{width:40px;height:40px;border-radius:11px;display:flex;align-items:center;justify-content:center;background:var(--stat-tint,var(--terracotta-100));color:var(--stat-fg,var(--terracotta-600));position:relative;}
        .stat-icon svg{width:20px;height:20px;}
        .stat-trend{font-size:12px;font-weight:700;padding:3px 8px;border-radius:20px;}
        .stat-trend.up{color:var(--success);background:var(--acacia-100);}
        .stat-trend.down{color:var(--danger);background:var(--danger-100);}
        .stat-value{font-family:'Raleway',sans-serif;font-size:30px;color:var(--coffee-900);position:relative;}
        .stat-label{font-size:13px;color:var(--ink-soft);margin-top:4px;position:relative;}

        .panel-grid{display:grid;grid-template-columns:1.55fr 1fr;gap:18px;margin-bottom:24px;}
        .panel{background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);overflow:hidden;}
        .panel-head{display:flex;align-items:center;justify-content:space-between;padding:18px 20px 14px;border-bottom:1px solid var(--line);}
        .panel-head h3{font-size:16.5px;}
        .panel-head .link{font-size:12.5px;font-weight:700;color:var(--terracotta-600);cursor:pointer;}
        .panel-body{padding:18px 20px 20px;}

        /* Bar Chart */
        .bars{display:flex;align-items:flex-end;gap:10px;height:170px;padding-top:10px;}
        .bar-col{flex:1;display:flex;flex-direction:column;align-items:center;gap:8px;height:100%;justify-content:flex-end;}
        .bar{
            width:100%;max-width:30px;border-radius:6px 6px 2px 2px;
            background:linear-gradient(180deg,var(--terracotta-500),var(--terracotta-600));
            transition:height .6s cubic-bezier(.2,.8,.2,1);position:relative;
        }
        .bar-col:nth-child(even) .bar{background:linear-gradient(180deg,var(--gold-500),#bb8636);}
        .bar-label{font-size:11px;color:var(--ink-soft);font-weight:600;}

        .donut-wrap{display:flex;align-items:center;gap:18px;}
        .legend{display:flex;flex-direction:column;gap:10px;flex:1;}
        .legend-item{display:flex;align-items:center;gap:9px;font-size:13px;}
        .legend-dot{width:10px;height:10px;border-radius:3px;flex:none;}
        .legend-item b{margin-left:auto;color:var(--coffee-900);}

        .activity-list{display:flex;flex-direction:column;gap:3px;}
        .activity-row{display:flex;gap:12px;padding:11px 0;border-bottom:1px dashed var(--line);}
        .activity-row:last-child{border-bottom:none;}
        .activity-ico{width:34px;height:34px;border-radius:9px;flex:none;display:flex;align-items:center;justify-content:center;background:var(--sand-100);color:var(--coffee-700);}
        .activity-ico svg{width:16px;height:16px;}
        .activity-text{font-size:13.5px;line-height:1.4;}
        .activity-text b{color:var(--coffee-900);}
        .activity-time{font-size:11.5px;color:var(--ink-soft);margin-top:2px;}


        /* Tables */
        .table-card{background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);overflow:hidden;}
        .table-toolbar{display:flex;align-items:center;gap:10px;padding:16px 18px;border-bottom:1px solid var(--line);flex-wrap:wrap;}
        .chip-filters{display:flex;gap:8px;flex-wrap:wrap;}
        .chip{padding:7px 14px;border-radius:20px;font-size:12.5px;font-weight:600;background:var(--sand-100);color:var(--coffee-700);border:1px solid transparent;cursor:pointer;display:inline-flex;align-items:center;text-decoration:none;}
        .chip.active{background:var(--coffee-900);color:#fff;}
        .table-search{display:flex;align-items:center;gap:8px;background:var(--sand-50);border:1.5px solid var(--line);border-radius:10px;padding:8px 12px;margin-left:auto;min-width:200px;}
        .table-search svg{width:15px;height:15px;color:var(--ink-soft);}
        .table-search input{border:none;background:transparent;outline:none;font-size:13.5px;width:100%;}
        .table-scroll{overflow-x:auto;}
        .table-pager{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 18px;border-top:1px solid var(--line);flex-wrap:wrap;}
        .pager-pages{display:flex;gap:6px;align-items:center;flex-wrap:wrap;}
        .pager-pages a,.pager-pages span.page{min-width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;padding:0 8px;border:1px solid var(--line);border-radius:8px;font-size:12.5px;font-weight:600;color:var(--coffee-700);text-decoration:none;background:var(--white);}
        .pager-pages a:hover{border-color:var(--gold-500);color:var(--terracotta-600);}
        .pager-pages span.active{background:var(--coffee-900);color:#fff;border-color:var(--coffee-900);}
        table{width:100%;border-collapse:collapse;min-width:680px;}
        thead th{
            text-align:left;font-size:11.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--ink-soft);
            padding:12px 18px;border-bottom:1px solid var(--line);background:var(--sand-50);font-weight:700;white-space:nowrap;
        }
        tbody td{padding:14px 18px;border-bottom:1px solid var(--line);font-size:13.5px;color:var(--coffee-900);}
        tbody tr:last-child td{border-bottom:none;}
        .table-pagination{padding:16px 18px;border-top:1px solid var(--line);display:flex;align-items:center;justify-content:center;gap:8px;}
        .table-pagination a,.table-pagination span{padding:8px 14px;border-radius:8px;font-size:12.5px;font-weight:600;border:1px solid var(--line);background:var(--white);color:var(--coffee-700);text-decoration:none;transition:all .2s;}
        .table-pagination a:hover{background:var(--sand-100);border-color:var(--coffee-300);}
        .table-pagination .active{background:var(--coffee-900);color:#fff;border-color:var(--coffee-900);}
        .table-pagination .disabled{opacity:.5;cursor:not-allowed;}
        tbody tr{transition:background .12s;}
        tbody tr:hover{background:var(--sand-50);}
        .cell-main{display:flex;align-items:center;gap:11px;}
        .thumb{width:42px;height:42px;border-radius:9px;object-fit:cover;flex:none;background:var(--sand-200);}
        .cell-title{font-weight:600;color:var(--coffee-900);}
        .cell-sub{font-size:12px;color:var(--ink-soft);margin-top:1px;}
        .tag{display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:20px;font-size:11.5px;font-weight:700;white-space:nowrap;}
        .tag-green{background:var(--acacia-100);color:var(--acacia-600);}
        .tag-gold{background:var(--gold-100);color:#8a6418;}
        .tag-red{background:var(--danger-100);color:var(--danger);}
        .tag-grey{background:var(--sand-200);color:var(--ink-soft);}
        .tag-terracotta{background:var(--terracotta-100);color:var(--terracotta-600);}
        .row-actions{display:flex;gap:6px;justify-content:flex-end;}
        .row-actions button{width:32px;height:32px;border-radius:8px;border:1px solid var(--line);background:var(--white);display:flex;align-items:center;justify-content:center;color:var(--coffee-700);}
        .row-actions button:hover{background:var(--sand-100);}
        .row-actions button svg{width:14.5px;height:14.5px;}
        .row-actions .danger:hover{background:var(--danger-100);color:var(--danger);border-color:var(--danger-100);}
        .row-actions .warn:hover{background:var(--gold-100);color:#8a6418;border-color:var(--gold-100);}
        .pwd-wrap{position:relative;}
        .pwd-wrap input{padding-right:42px;}
        .pwd-toggle{position:absolute;right:4px;top:50%;transform:translateY(-50%);width:32px;height:32px;border:none;background:transparent;cursor:pointer;font-size:14px;opacity:.7;border-radius:8px;}
        .pwd-toggle:hover{opacity:1;background:var(--sand-200);}
        .input-icon-wrap{position:relative;}
        .input-icon-wrap .input-icon{position:absolute;left:14px;top:0;bottom:0;margin:auto 0;width:16px;height:16px;color:var(--coffee-300);pointer-events:none;}
        .field .input-icon-wrap input,.field .input-icon-wrap textarea{padding-left:42px;}
        .settings-section{display:flex;align-items:center;gap:9px;margin:24px 0 4px;padding-top:8px;}
        .settings-section:first-of-type{margin-top:0;padding-top:0;}
        .settings-section::before{content:'';width:22px;height:3px;border-radius:2px;background:var(--gold-500);flex:none;}
        .settings-section h4{margin:0;font-size:12.5px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--coffee-700);}
        .empty-state{padding:60px 20px;text-align:center;color:var(--ink-soft);}
        .empty-state svg{width:46px;height:46px;color:var(--coffee-300);margin-bottom:12px;}
        .empty-state h4{margin-bottom:5px;color:var(--coffee-800);}
        .empty-state p{font-size:13.5px;}


        /* Buttons */
        .btn{
            display:inline-flex;align-items:center;justify-content:center;gap:8px;
            padding:12px 20px;border-radius:var(--radius-sm);border:none;
            font-weight:600;font-size:14.5px;transition:transform .12s, box-shadow .12s, background .15s;
        }
        .btn:active{transform:translateY(1px);}
        .btn-primary{background:var(--terracotta-600);color:#fff;box-shadow:0 6px 16px rgba(194,89,43,.32);}
        .btn-primary:hover{background:var(--terracotta-500);}
        .btn-ghost{background:transparent;color:var(--coffee-700);border:1.5px solid var(--line);}
        .btn-ghost:hover{background:var(--sand-100);}
        .btn-soft{background:var(--sand-100);color:var(--coffee-800);}
        .btn-soft:hover{background:var(--sand-200);}
        .btn-danger{background:var(--danger-100);color:var(--danger);}
        .btn-danger:hover{background:#efc6c2;}
        .btn-sm{padding:8px 13px;font-size:13px;}


        /* Gallery */
        .gallery-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:16px;}
        .gallery-card{position:relative;border-radius:var(--radius-md);overflow:hidden;aspect-ratio:1/1;box-shadow:var(--shadow-sm);border:1px solid var(--line);background:var(--sand-200);}
        .gallery-card img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .35s;}
        .gallery-card:hover img{transform:scale(1.06);}
        .gallery-overlay{
            position:absolute;inset:0;background:linear-gradient(180deg,rgba(36,20,8,0) 45%,rgba(36,20,8,.82));
            display:flex;flex-direction:column;justify-content:flex-end;padding:12px;opacity:0;transition:opacity .2s;
        }
        .gallery-card:hover .gallery-overlay{opacity:1;}
        .gallery-cap{color:#fff;font-size:12.5px;font-weight:600;margin-bottom:8px;line-height:1.3;}
        .gallery-actions{display:flex;gap:6px;}
        .gallery-actions button{flex:1;padding:6px;border-radius:7px;border:none;background:rgba(255,255,255,.18);color:#fff;backdrop-filter:blur(4px);font-size:11.5px;font-weight:600;display:flex;align-items:center;justify-content:center;gap:5px;}
        .gallery-actions button:hover{background:rgba(255,255,255,.3);}
        .gallery-badge{position:absolute;top:10px;left:10px;background:rgba(36,20,8,.65);color:#fff;font-size:10.5px;font-weight:700;padding:4px 9px;border-radius:20px;text-transform:uppercase;letter-spacing:.04em;backdrop-filter:blur(4px);}
        .add-tile{
            border:2px dashed var(--coffee-300);border-radius:var(--radius-md);aspect-ratio:1/1;
            display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;color:var(--coffee-500);
            background:var(--sand-100);font-size:12.5px;font-weight:600;cursor:pointer;
        }
        .add-tile:hover{background:var(--sand-200);border-color:var(--terracotta-500);color:var(--terracotta-600);}
        .add-tile svg{width:26px;height:26px;}


        /* Reviews */
        .review-card{display:flex;gap:14px;background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);padding:18px;box-shadow:var(--shadow-sm);margin-bottom:14px;}
        .review-avatar{width:44px;height:44px;border-radius:50%;flex:none;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:15px;background:linear-gradient(155deg,var(--terracotta-600),var(--gold-500));}
        .review-top{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:4px;flex-wrap:wrap;}
        .review-name{font-weight:700;color:var(--coffee-900);font-size:14.5px;}
        .review-tour{font-size:12px;color:var(--ink-soft);margin-top:1px;}
        .review-text{font-size:13.8px;color:var(--coffee-800);line-height:1.55;margin:8px 0 10px;}
        .review-actions{display:flex;gap:8px;}


        /* Messages */
        .msg-layout{display:grid;grid-template-columns:340px 1fr;gap:18px;align-items:start;}
        .msg-list{background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);overflow:hidden;box-shadow:var(--shadow-sm);}
        .msg-item{display:flex;gap:11px;padding:14px 16px;border-bottom:1px solid var(--line);cursor:pointer;position:relative;}
        .msg-item:hover{background:var(--sand-50);}
        .msg-item.active{background:var(--terracotta-100);}
        .msg-item.unread::before{content:"";position:absolute;left:6px;top:50%;transform:translateY(-50%);width:7px;height:7px;border-radius:50%;background:var(--terracotta-600);}
        .msg-item-name{font-weight:700;font-size:13.5px;color:var(--coffee-900);}
        .msg-item-prev{font-size:12px;color:var(--ink-soft);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:230px;}
        .msg-item-time{font-size:10.5px;color:var(--ink-soft);margin-left:auto;flex:none;}
        .msg-detail{background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);padding:24px;min-height:420px;display:flex;flex-direction:column;}
        .msg-detail-head{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1px solid var(--line);padding-bottom:16px;margin-bottom:16px;}
        .msg-detail-body{font-size:14.5px;line-height:1.7;color:var(--coffee-800);flex:1;}
        .msg-detail-foot{display:flex;gap:10px;margin-top:20px;padding-top:16px;border-top:1px solid var(--line);}


        /* Settings */
        .settings-grid{display:grid;grid-template-columns:240px 1fr;gap:24px;align-items:start;}
        .settings-grid-single{grid-template-columns:1fr;}
        .settings-nav{display:flex;flex-direction:column;gap:3px;background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);padding:10px;box-shadow:var(--shadow-sm);}
        .settings-nav button{
            display:flex;align-items:center;gap:10px;text-align:left;padding:11px 13px;border-radius:9px;border:none;background:transparent;
            font-size:13.8px;font-weight:600;color:var(--coffee-700);cursor:pointer;
        }
        .settings-nav button.active{background:var(--sand-100);color:var(--terracotta-600);}
        .settings-nav button svg{width:17px;height:17px;}
        .settings-panel{background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);padding:26px;}
        .settings-panel h3{font-size:17px;margin-bottom:18px;}
        .settings-pane{display:none;}
        .settings-pane.active{display:block;}
        .field{margin-bottom:16px;}
        .field label{display:block;font-size:12.5px;font-weight:600;color:var(--coffee-700);margin-bottom:7px;text-transform:uppercase;letter-spacing:.04em;}
        .field input,.field select,.field textarea{
            width:100%;padding:12px 14px;border:1.5px solid var(--line);border-radius:var(--radius-sm);
            background:var(--white);font-size:14.5px;color:var(--ink);transition:border-color .15s, box-shadow .15s;
        }
        .field input:focus,.field select:focus,.field textarea:focus{
            outline:none;border-color:var(--terracotta-500);box-shadow:0 0 0 3px var(--terracotta-100);
        }
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
        .toggle-row{display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid var(--line);}
        .toggle-row:last-child{border-bottom:none;}
        .toggle-text strong{display:block;font-size:14px;color:var(--coffee-900);margin-bottom:2px;}
        .toggle-text span{font-size:12.5px;color:var(--ink-soft);}
        .switch{width:42px;height:24px;border-radius:20px;background:var(--sand-200);position:relative;flex:none;border:none;cursor:pointer;transition:background .2s;}
        .switch::after{content:"";position:absolute;top:3px;left:3px;width:18px;height:18px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.25);transition:transform .2s;}
        .switch.on{background:var(--acacia-500);}
        .switch.on::after{transform:translateX(18px);}


        /* Modal */
        .modal-backdrop{
            position:fixed;inset:0;background:rgba(36,20,8,.5);backdrop-filter:blur(2px);
            display:none;align-items:flex-start;justify-content:center;z-index:400;padding:40px 20px;overflow-y:auto;
        }
        .modal-backdrop.show{display:flex;}
        .modal{
            background:var(--sand-50);border-radius:var(--radius-lg);width:100%;max-width:560px;
            box-shadow:var(--shadow-lg);animation:riseIn .3s cubic-bezier(.2,.8,.2,1);margin:auto;
        }
        @keyframes riseIn{from{opacity:0;transform:translateY(14px);}to{opacity:1;transform:translateY(0);}}
        .modal-head{display:flex;align-items:center;justify-content:space-between;padding:22px 24px;border-bottom:1px solid var(--line);}
        .modal-head h3{font-size:19px;}
        .modal-close{width:34px;height:34px;border-radius:9px;border:1px solid var(--line);background:var(--white);display:flex;align-items:center;justify-content:center;}
        .modal-body{padding:22px 24px;max-height:60vh;overflow-y:auto;}
        .modal-foot{display:flex;justify-content:flex-end;gap:10px;padding:18px 24px;border-top:1px solid var(--line);}


        /* Toast */
        #toastHost{position:fixed;bottom:24px;right:24px;z-index:600;display:flex;flex-direction:column;gap:10px;}
        .toast{
            background:var(--coffee-900);color:#fff;padding:13px 18px;border-radius:11px;font-size:13.5px;font-weight:600;
            box-shadow:var(--shadow-lg);display:flex;align-items:center;gap:10px;min-width:240px;animation:toastIn .3s ease;
        }
        .toast.success{background:var(--acacia-600);}
        .toast.error{background:var(--danger);}
        .toast svg{width:17px;height:17px;flex:none;}
        @keyframes toastIn{from{opacity:0;transform:translateX(20px);}to{opacity:1;transform:translateX(0);}}


        /* Responsive */
        .mobile-overlay{position:fixed;inset:0;background:rgba(36,20,8,.45);z-index:190;display:none;}
        .mobile-overlay.show{display:block;}
        @media (max-width:1180px){
            .stat-grid{grid-template-columns:repeat(2,1fr);}
            .panel-grid{grid-template-columns:1fr;}
            .settings-grid{grid-template-columns:1fr;}
            .msg-layout{grid-template-columns:1fr;}
        }
        @media (max-width:900px){
            .sidebar{transform:translateX(-100%);width:var(--sidebar-w);z-index:300;}
            .sidebar.mobile-open{transform:translateX(0);}
            .main{margin-left:0 !important;}
            .tb-search{display:none;}
        }
        @media (max-width:640px){
            .stat-grid{grid-template-columns:1fr;}
            .view-wrap{padding:16px;}
            .topbar{padding:0 14px;gap:10px;}
            .form-row{grid-template-columns:1fr;}
            .tb-live span{display:none;}
            .view-head h2{font-size:22px;}
        }
        @media (prefers-reduced-motion:reduce){
            *{animation-duration:.001ms !important;transition-duration:.001ms !important;}
        }
    </style>
</head>
<body>
    <div id="app" class="show">
        <div class="mobile-overlay" id="mobileOverlay" onclick="closeMobileSidebar()"></div>
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sb-brand">
                <img src="https://res.cloudinary.com/aenplcpl/image/upload/f_auto,q_auto,w_200/v1782890324/safari-logo-white_bexcal.png" alt="Tanzania Daily Tours & Safari" style="width: 38px; height: 38px; border-radius: 10px; flex: none;">
                <div class="sb-brand-text">
                    <strong>Tanzania Daily</strong>
                    <span>Tours & Safari · CMS</span>
                </div>
            </div>
            <nav class="sb-nav">
                <div class="sb-section-label">Overview</div>
                <a href="https://tanzaniadailytoursandsafari.com/live" class="sb-item ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="9" rx="1.5"></rect>
                        <rect x="14" y="3" width="7" height="5" rx="1.5"></rect>
                        <rect x="14" y="12" width="7" height="9" rx="1.5"></rect>
                        <rect x="3" y="16" width="7" height="5" rx="1.5"></rect>
                    </svg>
                    <span>Dashboard</span>
                </a>
                <div class="sb-section-label">Content</div>
                <a href="https://tanzaniadailytoursandsafari.com/live/destinations" class="sb-item ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 11 12 4l9 7"></path>
                        <path d="M5 10v10h14V10"></path>
                        <path d="M9 20v-6h6v6"></path>
                    </svg>
                    <span>Destinations</span>
                    <span class="badge" id="navDestCount">62</span>
                </a>
                <a href="https://tanzaniadailytoursandsafari.com/live/gallery" class="sb-item ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.8"></circle>
                        <path d="m21 15-5-5L5 21"></path>
                    </svg>
                    <span>Gallery</span>
                </a>
                <a href="https://tanzaniadailytoursandsafari.com/live/reviews" class="sb-item active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2.5 15.1 9 22 10 17 15 18.2 22 12 18.6 5.8 22 7 15 2 10 8.9 9"></polygon>
                    </svg>
                    <span>Reviews</span>
                    <span class="badge" id="navReviewCount">0</span>
                </a>
                <div class="sb-section-label">Operations</div>
                <a href="https://tanzaniadailytoursandsafari.com/live/bookings" class="sb-item ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="17" rx="2"></rect>
                        <path d="M16 2v4M8 2v4M3 9h18"></path>
                        <path d="m9 14 2 2 4-4"></path>
                    </svg>
                    <span>Bookings</span>
                    <span class="badge" id="navBookingCount">1</span>
                </a>
                <a href="https://tanzaniadailytoursandsafari.com/live/payments" class="sb-item ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                        <line x1="2" y1="10" x2="22" y2="10"></line>
                    </svg>
                    <span>Payments</span>
                    <span class="badge" id="navPaymentCount">5</span>
                </a>
                <a href="https://tanzaniadailytoursandsafari.com/live/messages" class="sb-item ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2Z"></path>
                    </svg>
                    <span>Messages</span>
                    <span class="badge" id="navMsgCount">0</span>
                </a>
                <div class="sb-section-label">System</div>
                <div class="sb-drop ">
                    <button type="button" class="sb-item sb-drop-toggle " onclick="toggleSbDrop(this)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1.04 1.56V21a2 2 0 0 1-4 0v-.09A1.7 1.7 0 0 0 9 19.4a1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.56-1.04H3a2 2 0 0 1 0-4h.09A1.7 1.7 0 0 0 4.6 9a1.7 1.7 0 0 0-.34-1.87l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1.04-1.56V3a2 2 0 0 1 4 0v.09A1.7 1.7 0 0 0 15 4.6a1.7 1.7 0 0 0 1.87-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9a1.7 1.7 0 0 0 1.56 1.04H21a2 2 0 0 1 0 4h-.09A1.7 1.7 0 0 0 19.4 15Z"></path>
                        </svg>
                        <span>Site Settings</span>
                        <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <div class="sb-drop-menu">
                        <a href="https://tanzaniadailytoursandsafari.com/live/settings?pane=general" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                                <rect x="9" y="9" width="6" height="6"></rect>
                            </svg>
                            General
                        </a>
                        <a href="https://tanzaniadailytoursandsafari.com/live/settings?pane=brand" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="13.5" cy="6.5" r=".5"></circle>
                                <circle cx="17.5" cy="10.5" r=".5"></circle>
                                <circle cx="8.5" cy="7.5" r=".5"></circle>
                                <circle cx="6.5" cy="12.5" r=".5"></circle>
                                <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C22 6.012 17.461 2 12 2Z"></path>
                            </svg>
                            Brand & Colors
                        </a>
                        <a href="https://tanzaniadailytoursandsafari.com/live/settings?pane=contact" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92Z"></path>
                            </svg>
                            Contact & Social
                        </a>
                        <a href="https://tanzaniadailytoursandsafari.com/live/settings?pane=notifications" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path>
                                <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path>
                            </svg>
                            Notifications
                        </a>
                        <a href="https://tanzaniadailytoursandsafari.com/live/users" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            Admin Users
                        </a>
                        <a href="https://tanzaniadailytoursandsafari.com/live/settings?pane=payments" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                                <line x1="2" y1="10" x2="22" y2="10"></line>
                            </svg>
                            Payments
                        </a>
                        <a href="https://tanzaniadailytoursandsafari.com/live/settings?pane=mail" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                            </svg>
                            Mail
                        </a>
                    </div>
                </div>
            </nav>
            <div class="sb-footer">
                <a href="https://tanzaniadailytoursandsafari.com/live/profile" class="sb-user" style="text-decoration:none;">
                    <div class="sb-avatar">
                        JE
                    </div>
                    <div class="sb-user-text">
                        <strong>Jeremia Developer</strong>
                        <span>jeremiat449@gmail.com</span>
                    </div>
                </a>
            </div>
        </aside>

        <!-- Main -->
        <div class="main" id="mainArea">
            <header class="topbar">
                <button class="tb-toggle" onclick="toggleSidebar()" aria-label="Toggle sidebar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="4" y1="7" x2="20" y2="7"></line>
                        <line x1="4" y1="12" x2="20" y2="12"></line>
                        <line x1="4" y1="17" x2="20" y2="17"></line>
                    </svg>
                </button>
                <div class="tb-search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" placeholder="Search tours, bookings, guests…">
                </div>
                <div class="tb-right">
                    <div class="tb-live"><span>Site live</span></div>
                    <a class="tb-iconbtn" href="https://tanzaniadailytoursandsafari.com" target="_blank" rel="noopener" aria-label="View live site" title="View live site">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                            <path d="M15 3h6v6"></path>
                            <path d="M10 14 21 3"></path>
                        </svg>
                    </a>
                    <form method="POST" action="https://tanzaniadailytoursandsafari.com/live/logout" style="display:inline;">
                        <input type="hidden" name="_token" value="w3Vlfvw2Bh8VfqolGpMryy5mbgT3npZg5cr409mV" autocomplete="off">                        <button type="submit" class="tb-iconbtn" aria-label="Log out" title="Log out">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16 17 21 12 16 7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                        </button>
                    </form>
                </div>
            </header>

            <div class="view-wrap">
                <!-- Session Messages -->
                
                <!-- Content -->
                <div class="view active">
    <div class="view-head">
        <div>
            <h2>Traveler Reviews</h2>
            <p class="sub">Approve, feature, or remove reviews submitted by guests.</p>
        </div>
        <div class="view-actions">
            <div class="chip-filters" style="margin:0;">
                <button class="chip active" data-filter="all" onclick="setReviewFilter('all')">All</button>
                <button class="chip" data-filter="Pending" onclick="setReviewFilter('Pending')">Pending</button>
                <button class="chip" data-filter="Published" onclick="setReviewFilter('Published')">Published</button>
            </div>
        </div>
    </div>

    <div id="reviewsList">
                        <div class="review-card" data-id="1" data-status="Published">
            <div class="review-avatar">S</div>
            <div style="flex:1;">
                <div class="review-top">
                    <div>
                        <div class="review-name">Sarah M.</div>
                        <div class="review-tour">Serengeti Safari</div>
                    </div>
                    <span class="tag tag-green">Published</span>
                </div>
                <div class="stars" style="font-size: 16px;">
                                                                        <span style="color: #D4A24C;">★</span>
                                                                                                <span style="color: #D4A24C;">★</span>
                                                                                                <span style="color: #D4A24C;">★</span>
                                                                                                <span style="color: #D4A24C;">★</span>
                                                                                                <span style="color: #D4A24C;">★</span>
                                                            </div>
                <p class="review-text">An absolutely magical experience. Our guide spotted a leopard in a tree within the first hour! The accommodations were perfect and every meal was delicious.</p>
                <div class="review-actions">
                                        <form action="https://tanzaniadailytoursandsafari.com/live/reviews/1" method="POST" onsubmit="return confirm('Delete this review?')">
                        <input type="hidden" name="_token" value="w3Vlfvw2Bh8VfqolGpMryy5mbgT3npZg5cr409mV" autocomplete="off">                        <input type="hidden" name="_method" value="DELETE">                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </div>
            </div>
        </div>
                <div class="review-card" data-id="2" data-status="Published">
            <div class="review-avatar">J</div>
            <div style="flex:1;">
                <div class="review-top">
                    <div>
                        <div class="review-name">James K.</div>
                        <div class="review-tour">Family Safari</div>
                    </div>
                    <span class="tag tag-green">Published</span>
                </div>
                <div class="stars" style="font-size: 16px;">
                                                                        <span style="color: #D4A24C;">★</span>
                                                                                                <span style="color: #D4A24C;">★</span>
                                                                                                <span style="color: #D4A24C;">★</span>
                                                                                                <span style="color: #D4A24C;">★</span>
                                                                                                <span style="color: #D4A24C;">★</span>
                                                            </div>
                <p class="review-text">We took our entire family including our 8-year-old. The team was so accommodating and patient. The kids still talk about the elephants every day.</p>
                <div class="review-actions">
                                        <form action="https://tanzaniadailytoursandsafari.com/live/reviews/2" method="POST" onsubmit="return confirm('Delete this review?')">
                        <input type="hidden" name="_token" value="w3Vlfvw2Bh8VfqolGpMryy5mbgT3npZg5cr409mV" autocomplete="off">                        <input type="hidden" name="_method" value="DELETE">                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </div>
            </div>
        </div>
                <div class="review-card" data-id="3" data-status="Published">
            <div class="review-avatar">E</div>
            <div style="flex:1;">
                <div class="review-top">
                    <div>
                        <div class="review-name">Elena R.</div>
                        <div class="review-tour">Kilimanjaro Trek</div>
                    </div>
                    <span class="tag tag-green">Published</span>
                </div>
                <div class="stars" style="font-size: 16px;">
                                                                        <span style="color: #D4A24C;">★</span>
                                                                                                <span style="color: #D4A24C;">★</span>
                                                                                                <span style="color: #D4A24C;">★</span>
                                                                                                <span style="color: #D4A24C;">★</span>
                                                                                                <span style="color: #D4A24C;">★</span>
                                                            </div>
                <p class="review-text">Climbing Kilimanjaro was a life-changing experience. The porters and guides were incredibly supportive. I couldn&#039;t have done it without their encouragement.</p>
                <div class="review-actions">
                                        <form action="https://tanzaniadailytoursandsafari.com/live/reviews/3" method="POST" onsubmit="return confirm('Delete this review?')">
                        <input type="hidden" name="_token" value="w3Vlfvw2Bh8VfqolGpMryy5mbgT3npZg5cr409mV" autocomplete="off">                        <input type="hidden" name="_method" value="DELETE">                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </div>
            </div>
        </div>
                    </div>
</div>

<script>
function setReviewFilter(filter) {
    document.querySelectorAll('.chip').forEach(c => c.classList.toggle('active', c.dataset.filter === filter));
    document.querySelectorAll('.review-card').forEach(card => {
        if (filter === 'all' || card.dataset.status === filter) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>

            </div>
        </div>
    </div>
    <div id="toastHost"></div>
    <script>
        // Auto-logout timer (5 minutes in milliseconds)
        const AUTO_LOGOUT_TIME = 5 * 60 * 1000;
        let autoLogoutTimer;

        // Reset the auto-logout timer on user activity
        function resetAutoLogoutTimer() {
            clearTimeout(autoLogoutTimer);
            autoLogoutTimer = setTimeout(() => {
                // Clear the session on the server side by redirecting to login
                window.location.href = "https://tanzaniadailytoursandsafari.com/live/login";
            }, AUTO_LOGOUT_TIME);
        }

        // Add event listeners for user activity
        ['click', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
            document.addEventListener(event, resetAutoLogoutTimer, true);
        });

        // Initialize the timer when the page loads
        resetAutoLogoutTimer();

        function toggleSidebar(){
            if(window.innerWidth <= 900){
                document.getElementById('sidebar').classList.toggle('mobile-open');
                document.getElementById('mobileOverlay').classList.toggle('show');
            } else {
                document.getElementById('sidebar').classList.toggle('collapsed');
            }
        }
        function closeMobileSidebar(){
            document.getElementById('sidebar').classList.remove('mobile-open');
            document.getElementById('mobileOverlay').classList.remove('show');
        }
        function toggleSbDrop(el){
            const drop = el.closest('.sb-drop');
            const wasOpen = drop.classList.contains('open');
            document.querySelectorAll('.sb-drop').forEach(d => d.classList.remove('open'));
            if(!wasOpen) drop.classList.add('open');
        }

        function toast(msg, type='default'){
            const host = document.getElementById('toastHost');
            const el = document.createElement('div');
            el.className = 'toast ' + (type==='success'?'success':type==='error'?'error':'');
            el.innerHTML = (type==='success' ? '<svg viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><path d=\"M20 6 9 17l-5-5\"></path></svg>' : '') + '<span>'+msg+'</span>';
            host.appendChild(el);
            setTimeout(()=>{ el.style.opacity='0'; el.style.transform='translateX(20px)'; el.style.transition='all .25s'; setTimeout(()=>el.remove(),250); }, 3000);
        }

        function openModal(id){ document.getElementById(id).classList.add('show'); }
        function closeModal(id){ document.getElementById(id).classList.remove('show'); }
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="w3Vlfvw2Bh8VfqolGpMryy5mbgT3npZg5cr409mV">
    <title>Bookings · Tanzania Daily Tours & Safari</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        :root{
            --sand-50:#FBF7EF;
            --sand-100:#F4ECDC;
            --sand-200:#E9DCC0;
            --coffee-900:#2A1B10;
            --coffee-800:#3B2718;
            --coffee-700:#4D3422;
            --coffee-500:#7A5C42;
            --coffee-300:#A98968;
            --terracotta-600:#C2592B;
            --terracotta-500:#D06B3A;
            --terracotta-100:#F6E1D3;
            --acacia-600:#5E6E3F;
            --acacia-500:#7A8450;
            --acacia-100:#E2E7D4;
            --gold-500:#D4A24C;
            --gold-100:#F7E9CB;
            --ink:#241408;
            --ink-soft:#6B5A48;
            --line:#E4D7C2;
            --white:#FFFFFF;
            --danger:#B33A3A;
            --danger-100:#F6DCDA;
            --success:#3F6B3F;
            --radius-sm:8px;
            --radius-md:14px;
            --radius-lg:20px;
            --shadow-sm:0 1px 2px rgba(42,27,16,.08);
            --shadow-md:0 8px 24px rgba(42,27,16,.10);
            --shadow-lg:0 20px 48px rgba(42,27,16,.18);
            --sidebar-w:264px;
            --sidebar-w-collapsed:76px;
            --topbar-h:72px;
        }

        *{box-sizing:border-box;}
        html,body{height:100%;}
        body{
            margin:0;
            font-family:'Raleway',sans-serif;
            background:var(--sand-50);
            color:var(--ink);
            -webkit-font-smoothing:antialiased;
            overflow-x:hidden;
        }
        ::selection{background:var(--terracotta-100);color:var(--coffee-900);}
        h1,h2,h3,h4{font-family:'Raleway',sans-serif;margin:0;color:var(--coffee-900);letter-spacing:-0.01em;}
        p{margin:0;}
        a{color:inherit;text-decoration:none;}
        button{font-family:inherit;cursor:pointer;}
        input,select,textarea{font-family:inherit;}
        .mono{font-family:'Raleway',sans-serif;}
        ::-webkit-scrollbar{width:9px;height:9px;}
        ::-webkit-scrollbar-track{background:transparent;}
        ::-webkit-scrollbar-thumb{background:var(--coffee-300);border-radius:10px;}
        ::-webkit-scrollbar-thumb:hover{background:var(--coffee-500);}


        /* Sidebar */
        .sidebar{
            position:fixed;top:0;left:0;bottom:0;width:var(--sidebar-w);z-index:200;
            background:var(--coffee-900);
            background-image:radial-gradient(circle at 0% 0%, rgba(212,162,76,.10), transparent 55%);
            display:flex;flex-direction:column;
            transition:width .25s ease, transform .25s ease;
            border-right:1px solid rgba(255,255,255,.06);
        }
        .sidebar.collapsed{width:var(--sidebar-w-collapsed);}
        .sb-brand{
            display:flex;align-items:center;gap:12px;padding:22px 20px;
            border-bottom:1px solid rgba(255,255,255,.08);min-height:var(--topbar-h);
        }
        .sb-mark{
            width:38px;height:38px;border-radius:10px;flex:none;
            background:linear-gradient(155deg,var(--terracotta-600),var(--gold-500));
            display:flex;align-items:center;justify-content:center;box-shadow:var(--shadow-sm);
        }
        .sb-mark svg{width:21px;height:21px;}
        .sb-brand-text{overflow:hidden;white-space:nowrap;}
        .sb-brand-text strong{display:block;color:#fff;font-family:'Raleway',sans-serif;font-size:15.5px;line-height:1.2;}
        .sb-brand-text span{display:block;color:var(--gold-500);font-size:11px;letter-spacing:.06em;text-transform:uppercase;font-weight:600;}
        .sidebar.collapsed .sb-brand-text{display:none;}
        .sb-nav{flex:1;overflow-y:auto;padding:16px 12px;}
        .sb-section-label{
            color:rgba(255,255,255,.32);font-size:10.5px;font-weight:700;letter-spacing:.09em;
            text-transform:uppercase;padding:14px 12px 8px;
        }
        .sidebar.collapsed .sb-section-label{display:none;}
        .sb-item{
            display:flex;align-items:center;gap:13px;padding:11px 12px;border-radius:10px;
            color:rgba(255,255,255,.62);font-size:14px;font-weight:500;margin-bottom:2px;
            position:relative;transition:background .15s,color .15s;white-space:nowrap;
        }
        .sb-item:hover{background:rgba(255,255,255,.06);color:#fff;}
        .sb-item.active{background:rgba(212,162,76,.16);color:var(--gold-500);}
        .sb-item.active::before{
            content:"";position:absolute;left:-12px;top:8px;bottom:8px;width:3px;border-radius:3px;
            background:var(--gold-500);
        }
        .sb-item svg{width:19px;height:19px;flex:none;}
        .sb-item .badge{
            margin-left:auto;background:var(--terracotta-600);color:#fff;font-size:11px;font-weight:700;
            padding:1px 7px;border-radius:20px;
        }
        .sidebar.collapsed .sb-item span:not(.badge){display:none;}
        .sidebar.collapsed .sb-item .badge{display:none;}
        .sidebar.collapsed .sb-item{justify-content:center;}
        .sb-drop{position:relative;}
        .sb-drop-toggle{width:100%;cursor:pointer;background:none;border:none;font-family:inherit;}
        .sb-drop-toggle .chev{margin-left:auto;opacity:.55;transition:transform .25s ease;width:15px;height:15px;flex:none;}
        .sb-drop.open .sb-drop-toggle .chev{transform:rotate(180deg);}
        .sb-drop-menu{display:none;margin:2px 0 4px;padding-left:12px;}
        .sb-drop.open .sb-drop-menu{display:block;}
        .sidebar.collapsed .sb-drop-menu{display:none;}
        .sb-drop-sub{display:flex;align-items:center;gap:9px;padding:9px 12px;border-radius:8px;margin-bottom:1px;color:rgba(255,255,255,.55);font-size:13px;font-weight:500;text-decoration:none;transition:background .15s ease,color .15s ease;}
        .sb-drop-sub:hover{color:#fff;background:rgba(255,255,255,.06);}
        .sb-drop-sub.active{color:var(--gold-500);background:rgba(212,162,76,.12);}
        .sb-drop-sub svg{width:14px;height:14px;flex:none;}
        .sb-footer{padding:14px 20px 20px;border-top:1px solid rgba(255,255,255,.08);}
        .sb-user{display:flex;align-items:center;gap:11px;}
        .sb-avatar{
            width:36px;height:36px;border-radius:50%;flex:none;
            background:linear-gradient(155deg,var(--acacia-500),var(--acacia-600));
            display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:13px;
        }
        .sb-user-text{overflow:hidden;white-space:nowrap;}
        .sb-user-text strong{display:block;color:#fff;font-size:13.5px;}
        .sb-user-text span{display:block;color:rgba(255,255,255,.5);font-size:11.5px;}
        .sidebar.collapsed .sb-user-text{display:none;}


        /* Main */
        .main{margin-left:var(--sidebar-w);transition:margin-left .25s ease;min-height:100vh;display:flex;flex-direction:column;}
        .sidebar.collapsed ~ .main{margin-left:var(--sidebar-w-collapsed);}
        .topbar{
            position:sticky;top:0;z-index:100;height:var(--topbar-h);
            background:rgba(251,247,239,.86);backdrop-filter:blur(10px);
            border-bottom:1px solid var(--line);
            display:flex;align-items:center;gap:16px;padding:0 28px;
        }
        .tb-toggle{
            width:38px;height:38px;border-radius:10px;border:1.5px solid var(--line);background:var(--white);
            display:flex;align-items:center;justify-content:center;flex:none;
        }
        .tb-toggle svg{width:18px;height:18px;color:var(--coffee-700);}
        .tb-toggle:hover{background:var(--sand-100);}
        .tb-search{
            flex:1;max-width:420px;display:flex;align-items:center;gap:10px;
            background:var(--white);border:1.5px solid var(--line);border-radius:11px;padding:9px 14px;
        }
        .tb-search svg{width:17px;height:17px;color:var(--ink-soft);flex:none;}
        .tb-search input{border:none;outline:none;background:transparent;font-size:14px;width:100%;color:var(--ink);}
        .tb-right{margin-left:auto;display:flex;align-items:center;gap:10px;}
        .tb-iconbtn{
            width:40px;height:40px;border-radius:11px;border:1.5px solid var(--line);background:var(--white);
            display:flex;align-items:center;justify-content:center;position:relative;color:var(--coffee-700);
        }
        .tb-iconbtn:hover{background:var(--sand-100);}
        .tb-iconbtn svg{width:18px;height:18px;}
        .tb-dot{
            position:absolute;top:7px;right:7px;width:8px;height:8px;border-radius:50%;
            background:var(--terracotta-600);border:2px solid var(--sand-50);
        }
        .tb-live{
            display:flex;align-items:center;gap:7px;background:var(--acacia-100);color:var(--acacia-600);
            padding:7px 13px;border-radius:20px;font-size:12.5px;font-weight:700;
        }
        .tb-live::before{
            content:"";width:7px;height:7px;border-radius:50%;background:var(--acacia-600);box-shadow:0 0 0 0 rgba(94,110,63,.5);animation:pulse 2s infinite;
        }
        @keyframes pulse{
            0%{box-shadow:0 0 0 0 rgba(94,110,63,.45);}
            70%{box-shadow:0 0 0 7px rgba(94,110,63,0);}
            100%{box-shadow:0 0 0 0 rgba(94,110,63,0);}
        }
        .view-wrap{padding:28px;flex:1;}
        .view{display:none;animation:fadeUp .35s ease;}
        .view.active{display:block;}
        @keyframes fadeUp{from{opacity:0;transform:translateY(8px);}to{opacity:1;transform:translateY(0);}}
        .view-head{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:24px;flex-wrap:wrap;}
        .view-head h2{font-size:27px;}
        .view-head .sub{color:var(--ink-soft);font-size:14px;margin-top:5px;}
        .view-actions{display:flex;gap:10px;flex-wrap:wrap;}


        /* Stats */
        .stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:24px;}
        .stat-card{
            background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);
            padding:20px 20px 18px;box-shadow:var(--shadow-sm);position:relative;overflow:hidden;
        }
        .stat-card::after{content:"";position:absolute;right:-20px;top:-20px;width:90px;height:90px;border-radius:50%;background:var(--stat-tint,var(--terracotta-100));opacity:.5;}
        .stat-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
        .stat-icon{width:40px;height:40px;border-radius:11px;display:flex;align-items:center;justify-content:center;background:var(--stat-tint,var(--terracotta-100));color:var(--stat-fg,var(--terracotta-600));position:relative;}
        .stat-icon svg{width:20px;height:20px;}
        .stat-trend{font-size:12px;font-weight:700;padding:3px 8px;border-radius:20px;}
        .stat-trend.up{color:var(--success);background:var(--acacia-100);}
        .stat-trend.down{color:var(--danger);background:var(--danger-100);}
        .stat-value{font-family:'Raleway',sans-serif;font-size:30px;color:var(--coffee-900);position:relative;}
        .stat-label{font-size:13px;color:var(--ink-soft);margin-top:4px;position:relative;}

        .panel-grid{display:grid;grid-template-columns:1.55fr 1fr;gap:18px;margin-bottom:24px;}
        .panel{background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);overflow:hidden;}
        .panel-head{display:flex;align-items:center;justify-content:space-between;padding:18px 20px 14px;border-bottom:1px solid var(--line);}
        .panel-head h3{font-size:16.5px;}
        .panel-head .link{font-size:12.5px;font-weight:700;color:var(--terracotta-600);cursor:pointer;}
        .panel-body{padding:18px 20px 20px;}

        /* Bar Chart */
        .bars{display:flex;align-items:flex-end;gap:10px;height:170px;padding-top:10px;}
        .bar-col{flex:1;display:flex;flex-direction:column;align-items:center;gap:8px;height:100%;justify-content:flex-end;}
        .bar{
            width:100%;max-width:30px;border-radius:6px 6px 2px 2px;
            background:linear-gradient(180deg,var(--terracotta-500),var(--terracotta-600));
            transition:height .6s cubic-bezier(.2,.8,.2,1);position:relative;
        }
        .bar-col:nth-child(even) .bar{background:linear-gradient(180deg,var(--gold-500),#bb8636);}
        .bar-label{font-size:11px;color:var(--ink-soft);font-weight:600;}

        .donut-wrap{display:flex;align-items:center;gap:18px;}
        .legend{display:flex;flex-direction:column;gap:10px;flex:1;}
        .legend-item{display:flex;align-items:center;gap:9px;font-size:13px;}
        .legend-dot{width:10px;height:10px;border-radius:3px;flex:none;}
        .legend-item b{margin-left:auto;color:var(--coffee-900);}

        .activity-list{display:flex;flex-direction:column;gap:3px;}
        .activity-row{display:flex;gap:12px;padding:11px 0;border-bottom:1px dashed var(--line);}
        .activity-row:last-child{border-bottom:none;}
        .activity-ico{width:34px;height:34px;border-radius:9px;flex:none;display:flex;align-items:center;justify-content:center;background:var(--sand-100);color:var(--coffee-700);}
        .activity-ico svg{width:16px;height:16px;}
        .activity-text{font-size:13.5px;line-height:1.4;}
        .activity-text b{color:var(--coffee-900);}
        .activity-time{font-size:11.5px;color:var(--ink-soft);margin-top:2px;}


        /* Tables */
        .table-card{background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);overflow:hidden;}
        .table-toolbar{display:flex;align-items:center;gap:10px;padding:16px 18px;border-bottom:1px solid var(--line);flex-wrap:wrap;}
        .chip-filters{display:flex;gap:8px;flex-wrap:wrap;}
        .chip{padding:7px 14px;border-radius:20px;font-size:12.5px;font-weight:600;background:var(--sand-100);color:var(--coffee-700);border:1px solid transparent;cursor:pointer;display:inline-flex;align-items:center;text-decoration:none;}
        .chip.active{background:var(--coffee-900);color:#fff;}
        .table-search{display:flex;align-items:center;gap:8px;background:var(--sand-50);border:1.5px solid var(--line);border-radius:10px;padding:8px 12px;margin-left:auto;min-width:200px;}
        .table-search svg{width:15px;height:15px;color:var(--ink-soft);}
        .table-search input{border:none;background:transparent;outline:none;font-size:13.5px;width:100%;}
        .table-scroll{overflow-x:auto;}
        .table-pager{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 18px;border-top:1px solid var(--line);flex-wrap:wrap;}
        .pager-pages{display:flex;gap:6px;align-items:center;flex-wrap:wrap;}
        .pager-pages a,.pager-pages span.page{min-width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;padding:0 8px;border:1px solid var(--line);border-radius:8px;font-size:12.5px;font-weight:600;color:var(--coffee-700);text-decoration:none;background:var(--white);}
        .pager-pages a:hover{border-color:var(--gold-500);color:var(--terracotta-600);}
        .pager-pages span.active{background:var(--coffee-900);color:#fff;border-color:var(--coffee-900);}
        table{width:100%;border-collapse:collapse;min-width:680px;}
        thead th{
            text-align:left;font-size:11.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--ink-soft);
            padding:12px 18px;border-bottom:1px solid var(--line);background:var(--sand-50);font-weight:700;white-space:nowrap;
        }
        tbody td{padding:14px 18px;border-bottom:1px solid var(--line);font-size:13.5px;color:var(--coffee-900);}
        tbody tr:last-child td{border-bottom:none;}
        .table-pagination{padding:16px 18px;border-top:1px solid var(--line);display:flex;align-items:center;justify-content:center;gap:8px;}
        .table-pagination a,.table-pagination span{padding:8px 14px;border-radius:8px;font-size:12.5px;font-weight:600;border:1px solid var(--line);background:var(--white);color:var(--coffee-700);text-decoration:none;transition:all .2s;}
        .table-pagination a:hover{background:var(--sand-100);border-color:var(--coffee-300);}
        .table-pagination .active{background:var(--coffee-900);color:#fff;border-color:var(--coffee-900);}
        .table-pagination .disabled{opacity:.5;cursor:not-allowed;}
        tbody tr{transition:background .12s;}
        tbody tr:hover{background:var(--sand-50);}
        .cell-main{display:flex;align-items:center;gap:11px;}
        .thumb{width:42px;height:42px;border-radius:9px;object-fit:cover;flex:none;background:var(--sand-200);}
        .cell-title{font-weight:600;color:var(--coffee-900);}
        .cell-sub{font-size:12px;color:var(--ink-soft);margin-top:1px;}
        .tag{display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:20px;font-size:11.5px;font-weight:700;white-space:nowrap;}
        .tag-green{background:var(--acacia-100);color:var(--acacia-600);}
        .tag-gold{background:var(--gold-100);color:#8a6418;}
        .tag-red{background:var(--danger-100);color:var(--danger);}
        .tag-grey{background:var(--sand-200);color:var(--ink-soft);}
        .tag-terracotta{background:var(--terracotta-100);color:var(--terracotta-600);}
        .row-actions{display:flex;gap:6px;justify-content:flex-end;}
        .row-actions button{width:32px;height:32px;border-radius:8px;border:1px solid var(--line);background:var(--white);display:flex;align-items:center;justify-content:center;color:var(--coffee-700);}
        .row-actions button:hover{background:var(--sand-100);}
        .row-actions button svg{width:14.5px;height:14.5px;}
        .row-actions .danger:hover{background:var(--danger-100);color:var(--danger);border-color:var(--danger-100);}
        .row-actions .warn:hover{background:var(--gold-100);color:#8a6418;border-color:var(--gold-100);}
        .pwd-wrap{position:relative;}
        .pwd-wrap input{padding-right:42px;}
        .pwd-toggle{position:absolute;right:4px;top:50%;transform:translateY(-50%);width:32px;height:32px;border:none;background:transparent;cursor:pointer;font-size:14px;opacity:.7;border-radius:8px;}
        .pwd-toggle:hover{opacity:1;background:var(--sand-200);}
        .input-icon-wrap{position:relative;}
        .input-icon-wrap .input-icon{position:absolute;left:14px;top:0;bottom:0;margin:auto 0;width:16px;height:16px;color:var(--coffee-300);pointer-events:none;}
        .field .input-icon-wrap input,.field .input-icon-wrap textarea{padding-left:42px;}
        .settings-section{display:flex;align-items:center;gap:9px;margin:24px 0 4px;padding-top:8px;}
        .settings-section:first-of-type{margin-top:0;padding-top:0;}
        .settings-section::before{content:'';width:22px;height:3px;border-radius:2px;background:var(--gold-500);flex:none;}
        .settings-section h4{margin:0;font-size:12.5px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--coffee-700);}
        .empty-state{padding:60px 20px;text-align:center;color:var(--ink-soft);}
        .empty-state svg{width:46px;height:46px;color:var(--coffee-300);margin-bottom:12px;}
        .empty-state h4{margin-bottom:5px;color:var(--coffee-800);}
        .empty-state p{font-size:13.5px;}


        /* Buttons */
        .btn{
            display:inline-flex;align-items:center;justify-content:center;gap:8px;
            padding:12px 20px;border-radius:var(--radius-sm);border:none;
            font-weight:600;font-size:14.5px;transition:transform .12s, box-shadow .12s, background .15s;
        }
        .btn:active{transform:translateY(1px);}
        .btn-primary{background:var(--terracotta-600);color:#fff;box-shadow:0 6px 16px rgba(194,89,43,.32);}
        .btn-primary:hover{background:var(--terracotta-500);}
        .btn-ghost{background:transparent;color:var(--coffee-700);border:1.5px solid var(--line);}
        .btn-ghost:hover{background:var(--sand-100);}
        .btn-soft{background:var(--sand-100);color:var(--coffee-800);}
        .btn-soft:hover{background:var(--sand-200);}
        .btn-danger{background:var(--danger-100);color:var(--danger);}
        .btn-danger:hover{background:#efc6c2;}
        .btn-sm{padding:8px 13px;font-size:13px;}


        /* Gallery */
        .gallery-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:16px;}
        .gallery-card{position:relative;border-radius:var(--radius-md);overflow:hidden;aspect-ratio:1/1;box-shadow:var(--shadow-sm);border:1px solid var(--line);background:var(--sand-200);}
        .gallery-card img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .35s;}
        .gallery-card:hover img{transform:scale(1.06);}
        .gallery-overlay{
            position:absolute;inset:0;background:linear-gradient(180deg,rgba(36,20,8,0) 45%,rgba(36,20,8,.82));
            display:flex;flex-direction:column;justify-content:flex-end;padding:12px;opacity:0;transition:opacity .2s;
        }
        .gallery-card:hover .gallery-overlay{opacity:1;}
        .gallery-cap{color:#fff;font-size:12.5px;font-weight:600;margin-bottom:8px;line-height:1.3;}
        .gallery-actions{display:flex;gap:6px;}
        .gallery-actions button{flex:1;padding:6px;border-radius:7px;border:none;background:rgba(255,255,255,.18);color:#fff;backdrop-filter:blur(4px);font-size:11.5px;font-weight:600;display:flex;align-items:center;justify-content:center;gap:5px;}
        .gallery-actions button:hover{background:rgba(255,255,255,.3);}
        .gallery-badge{position:absolute;top:10px;left:10px;background:rgba(36,20,8,.65);color:#fff;font-size:10.5px;font-weight:700;padding:4px 9px;border-radius:20px;text-transform:uppercase;letter-spacing:.04em;backdrop-filter:blur(4px);}
        .add-tile{
            border:2px dashed var(--coffee-300);border-radius:var(--radius-md);aspect-ratio:1/1;
            display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;color:var(--coffee-500);
            background:var(--sand-100);font-size:12.5px;font-weight:600;cursor:pointer;
        }
        .add-tile:hover{background:var(--sand-200);border-color:var(--terracotta-500);color:var(--terracotta-600);}
        .add-tile svg{width:26px;height:26px;}


        /* Reviews */
        .review-card{display:flex;gap:14px;background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);padding:18px;box-shadow:var(--shadow-sm);margin-bottom:14px;}
        .review-avatar{width:44px;height:44px;border-radius:50%;flex:none;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:15px;background:linear-gradient(155deg,var(--terracotta-600),var(--gold-500));}
        .review-top{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:4px;flex-wrap:wrap;}
        .review-name{font-weight:700;color:var(--coffee-900);font-size:14.5px;}
        .review-tour{font-size:12px;color:var(--ink-soft);margin-top:1px;}
        .review-text{font-size:13.8px;color:var(--coffee-800);line-height:1.55;margin:8px 0 10px;}
        .review-actions{display:flex;gap:8px;}


        /* Messages */
        .msg-layout{display:grid;grid-template-columns:340px 1fr;gap:18px;align-items:start;}
        .msg-list{background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);overflow:hidden;box-shadow:var(--shadow-sm);}
        .msg-item{display:flex;gap:11px;padding:14px 16px;border-bottom:1px solid var(--line);cursor:pointer;position:relative;}
        .msg-item:hover{background:var(--sand-50);}
        .msg-item.active{background:var(--terracotta-100);}
        .msg-item.unread::before{content:"";position:absolute;left:6px;top:50%;transform:translateY(-50%);width:7px;height:7px;border-radius:50%;background:var(--terracotta-600);}
        .msg-item-name{font-weight:700;font-size:13.5px;color:var(--coffee-900);}
        .msg-item-prev{font-size:12px;color:var(--ink-soft);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:230px;}
        .msg-item-time{font-size:10.5px;color:var(--ink-soft);margin-left:auto;flex:none;}
        .msg-detail{background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);padding:24px;min-height:420px;display:flex;flex-direction:column;}
        .msg-detail-head{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1px solid var(--line);padding-bottom:16px;margin-bottom:16px;}
        .msg-detail-body{font-size:14.5px;line-height:1.7;color:var(--coffee-800);flex:1;}
        .msg-detail-foot{display:flex;gap:10px;margin-top:20px;padding-top:16px;border-top:1px solid var(--line);}


        /* Settings */
        .settings-grid{display:grid;grid-template-columns:240px 1fr;gap:24px;align-items:start;}
        .settings-grid-single{grid-template-columns:1fr;}
        .settings-nav{display:flex;flex-direction:column;gap:3px;background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);padding:10px;box-shadow:var(--shadow-sm);}
        .settings-nav button{
            display:flex;align-items:center;gap:10px;text-align:left;padding:11px 13px;border-radius:9px;border:none;background:transparent;
            font-size:13.8px;font-weight:600;color:var(--coffee-700);cursor:pointer;
        }
        .settings-nav button.active{background:var(--sand-100);color:var(--terracotta-600);}
        .settings-nav button svg{width:17px;height:17px;}
        .settings-panel{background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);padding:26px;}
        .settings-panel h3{font-size:17px;margin-bottom:18px;}
        .settings-pane{display:none;}
        .settings-pane.active{display:block;}
        .field{margin-bottom:16px;}
        .field label{display:block;font-size:12.5px;font-weight:600;color:var(--coffee-700);margin-bottom:7px;text-transform:uppercase;letter-spacing:.04em;}
        .field input,.field select,.field textarea{
            width:100%;padding:12px 14px;border:1.5px solid var(--line);border-radius:var(--radius-sm);
            background:var(--white);font-size:14.5px;color:var(--ink);transition:border-color .15s, box-shadow .15s;
        }
        .field input:focus,.field select:focus,.field textarea:focus{
            outline:none;border-color:var(--terracotta-500);box-shadow:0 0 0 3px var(--terracotta-100);
        }
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
        .toggle-row{display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid var(--line);}
        .toggle-row:last-child{border-bottom:none;}
        .toggle-text strong{display:block;font-size:14px;color:var(--coffee-900);margin-bottom:2px;}
        .toggle-text span{font-size:12.5px;color:var(--ink-soft);}
        .switch{width:42px;height:24px;border-radius:20px;background:var(--sand-200);position:relative;flex:none;border:none;cursor:pointer;transition:background .2s;}
        .switch::after{content:"";position:absolute;top:3px;left:3px;width:18px;height:18px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.25);transition:transform .2s;}
        .switch.on{background:var(--acacia-500);}
        .switch.on::after{transform:translateX(18px);}


        /* Modal */
        .modal-backdrop{
            position:fixed;inset:0;background:rgba(36,20,8,.5);backdrop-filter:blur(2px);
            display:none;align-items:flex-start;justify-content:center;z-index:400;padding:40px 20px;overflow-y:auto;
        }
        .modal-backdrop.show{display:flex;}
        .modal{
            background:var(--sand-50);border-radius:var(--radius-lg);width:100%;max-width:560px;
            box-shadow:var(--shadow-lg);animation:riseIn .3s cubic-bezier(.2,.8,.2,1);margin:auto;
        }
        @keyframes riseIn{from{opacity:0;transform:translateY(14px);}to{opacity:1;transform:translateY(0);}}
        .modal-head{display:flex;align-items:center;justify-content:space-between;padding:22px 24px;border-bottom:1px solid var(--line);}
        .modal-head h3{font-size:19px;}
        .modal-close{width:34px;height:34px;border-radius:9px;border:1px solid var(--line);background:var(--white);display:flex;align-items:center;justify-content:center;}
        .modal-body{padding:22px 24px;max-height:60vh;overflow-y:auto;}
        .modal-foot{display:flex;justify-content:flex-end;gap:10px;padding:18px 24px;border-top:1px solid var(--line);}


        /* Toast */
        #toastHost{position:fixed;bottom:24px;right:24px;z-index:600;display:flex;flex-direction:column;gap:10px;}
        .toast{
            background:var(--coffee-900);color:#fff;padding:13px 18px;border-radius:11px;font-size:13.5px;font-weight:600;
            box-shadow:var(--shadow-lg);display:flex;align-items:center;gap:10px;min-width:240px;animation:toastIn .3s ease;
        }
        .toast.success{background:var(--acacia-600);}
        .toast.error{background:var(--danger);}
        .toast svg{width:17px;height:17px;flex:none;}
        @keyframes toastIn{from{opacity:0;transform:translateX(20px);}to{opacity:1;transform:translateX(0);}}


        /* Responsive */
        .mobile-overlay{position:fixed;inset:0;background:rgba(36,20,8,.45);z-index:190;display:none;}
        .mobile-overlay.show{display:block;}
        @media (max-width:1180px){
            .stat-grid{grid-template-columns:repeat(2,1fr);}
            .panel-grid{grid-template-columns:1fr;}
            .settings-grid{grid-template-columns:1fr;}
            .msg-layout{grid-template-columns:1fr;}
        }
        @media (max-width:900px){
            .sidebar{transform:translateX(-100%);width:var(--sidebar-w);z-index:300;}
            .sidebar.mobile-open{transform:translateX(0);}
            .main{margin-left:0 !important;}
            .tb-search{display:none;}
        }
        @media (max-width:640px){
            .stat-grid{grid-template-columns:1fr;}
            .view-wrap{padding:16px;}
            .topbar{padding:0 14px;gap:10px;}
            .form-row{grid-template-columns:1fr;}
            .tb-live span{display:none;}
            .view-head h2{font-size:22px;}
        }
        @media (prefers-reduced-motion:reduce){
            *{animation-duration:.001ms !important;transition-duration:.001ms !important;}
        }
    </style>
</head>
<body>
    <div id="app" class="show">
        <div class="mobile-overlay" id="mobileOverlay" onclick="closeMobileSidebar()"></div>
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sb-brand">
                <img src="https://res.cloudinary.com/aenplcpl/image/upload/f_auto,q_auto,w_200/v1782890324/safari-logo-white_bexcal.png" alt="Tanzania Daily Tours & Safari" style="width: 38px; height: 38px; border-radius: 10px; flex: none;">
                <div class="sb-brand-text">
                    <strong>Tanzania Daily</strong>
                    <span>Tours & Safari · CMS</span>
                </div>
            </div>
            <nav class="sb-nav">
                <div class="sb-section-label">Overview</div>
                <a href="https://tanzaniadailytoursandsafari.com/live" class="sb-item ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="9" rx="1.5"></rect>
                        <rect x="14" y="3" width="7" height="5" rx="1.5"></rect>
                        <rect x="14" y="12" width="7" height="9" rx="1.5"></rect>
                        <rect x="3" y="16" width="7" height="5" rx="1.5"></rect>
                    </svg>
                    <span>Dashboard</span>
                </a>
                <div class="sb-section-label">Content</div>
                <a href="https://tanzaniadailytoursandsafari.com/live/destinations" class="sb-item ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 11 12 4l9 7"></path>
                        <path d="M5 10v10h14V10"></path>
                        <path d="M9 20v-6h6v6"></path>
                    </svg>
                    <span>Destinations</span>
                    <span class="badge" id="navDestCount">62</span>
                </a>
                <a href="https://tanzaniadailytoursandsafari.com/live/gallery" class="sb-item ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.8"></circle>
                        <path d="m21 15-5-5L5 21"></path>
                    </svg>
                    <span>Gallery</span>
                </a>
                <a href="https://tanzaniadailytoursandsafari.com/live/reviews" class="sb-item ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2.5 15.1 9 22 10 17 15 18.2 22 12 18.6 5.8 22 7 15 2 10 8.9 9"></polygon>
                    </svg>
                    <span>Reviews</span>
                    <span class="badge" id="navReviewCount">0</span>
                </a>
                <div class="sb-section-label">Operations</div>
                <a href="https://tanzaniadailytoursandsafari.com/live/bookings" class="sb-item active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="17" rx="2"></rect>
                        <path d="M16 2v4M8 2v4M3 9h18"></path>
                        <path d="m9 14 2 2 4-4"></path>
                    </svg>
                    <span>Bookings</span>
                    <span class="badge" id="navBookingCount">1</span>
                </a>
                <a href="https://tanzaniadailytoursandsafari.com/live/payments" class="sb-item ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                        <line x1="2" y1="10" x2="22" y2="10"></line>
                    </svg>
                    <span>Payments</span>
                    <span class="badge" id="navPaymentCount">5</span>
                </a>
                <a href="https://tanzaniadailytoursandsafari.com/live/messages" class="sb-item ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2Z"></path>
                    </svg>
                    <span>Messages</span>
                    <span class="badge" id="navMsgCount">0</span>
                </a>
                <div class="sb-section-label">System</div>
                <div class="sb-drop ">
                    <button type="button" class="sb-item sb-drop-toggle " onclick="toggleSbDrop(this)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1.04 1.56V21a2 2 0 0 1-4 0v-.09A1.7 1.7 0 0 0 9 19.4a1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.56-1.04H3a2 2 0 0 1 0-4h.09A1.7 1.7 0 0 0 4.6 9a1.7 1.7 0 0 0-.34-1.87l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1.04-1.56V3a2 2 0 0 1 4 0v.09A1.7 1.7 0 0 0 15 4.6a1.7 1.7 0 0 0 1.87-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9a1.7 1.7 0 0 0 1.56 1.04H21a2 2 0 0 1 0 4h-.09A1.7 1.7 0 0 0 19.4 15Z"></path>
                        </svg>
                        <span>Site Settings</span>
                        <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <div class="sb-drop-menu">
                        <a href="https://tanzaniadailytoursandsafari.com/live/settings?pane=general" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                                <rect x="9" y="9" width="6" height="6"></rect>
                            </svg>
                            General
                        </a>
                        <a href="https://tanzaniadailytoursandsafari.com/live/settings?pane=brand" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="13.5" cy="6.5" r=".5"></circle>
                                <circle cx="17.5" cy="10.5" r=".5"></circle>
                                <circle cx="8.5" cy="7.5" r=".5"></circle>
                                <circle cx="6.5" cy="12.5" r=".5"></circle>
                                <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C22 6.012 17.461 2 12 2Z"></path>
                            </svg>
                            Brand & Colors
                        </a>
                        <a href="https://tanzaniadailytoursandsafari.com/live/settings?pane=contact" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92Z"></path>
                            </svg>
                            Contact & Social
                        </a>
                        <a href="https://tanzaniadailytoursandsafari.com/live/settings?pane=notifications" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path>
                                <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path>
                            </svg>
                            Notifications
                        </a>
                        <a href="https://tanzaniadailytoursandsafari.com/live/users" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            Admin Users
                        </a>
                        <a href="https://tanzaniadailytoursandsafari.com/live/settings?pane=payments" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                                <line x1="2" y1="10" x2="22" y2="10"></line>
                            </svg>
                            Payments
                        </a>
                        <a href="https://tanzaniadailytoursandsafari.com/live/settings?pane=mail" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                            </svg>
                            Mail
                        </a>
                    </div>
                </div>
            </nav>
            <div class="sb-footer">
                <a href="https://tanzaniadailytoursandsafari.com/live/profile" class="sb-user" style="text-decoration:none;">
                    <div class="sb-avatar">
                        JE
                    </div>
                    <div class="sb-user-text">
                        <strong>Jeremia Developer</strong>
                        <span>jeremiat449@gmail.com</span>
                    </div>
                </a>
            </div>
        </aside>

        <!-- Main -->
        <div class="main" id="mainArea">
            <header class="topbar">
                <button class="tb-toggle" onclick="toggleSidebar()" aria-label="Toggle sidebar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="4" y1="7" x2="20" y2="7"></line>
                        <line x1="4" y1="12" x2="20" y2="12"></line>
                        <line x1="4" y1="17" x2="20" y2="17"></line>
                    </svg>
                </button>
                <div class="tb-search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" placeholder="Search tours, bookings, guests…">
                </div>
                <div class="tb-right">
                    <div class="tb-live"><span>Site live</span></div>
                    <a class="tb-iconbtn" href="https://tanzaniadailytoursandsafari.com" target="_blank" rel="noopener" aria-label="View live site" title="View live site">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                            <path d="M15 3h6v6"></path>
                            <path d="M10 14 21 3"></path>
                        </svg>
                    </a>
                    <form method="POST" action="https://tanzaniadailytoursandsafari.com/live/logout" style="display:inline;">
                        <input type="hidden" name="_token" value="w3Vlfvw2Bh8VfqolGpMryy5mbgT3npZg5cr409mV" autocomplete="off">                        <button type="submit" class="tb-iconbtn" aria-label="Log out" title="Log out">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16 17 21 12 16 7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                        </button>
                    </form>
                </div>
            </header>

            <div class="view-wrap">
                <!-- Session Messages -->
                
                <!-- Content -->
                <div class="view active">
    <style>
        .paydrop{position:relative;}
        .paydrop-menu{
            display:none;
            position:absolute;
            right:0;
            top:calc(100% + 6px);
            background:var(--white);
            border:1px solid var(--line);
            border-radius:12px;
            box-shadow:0 12px 24px rgba(133,66,8,0.12);
            width:280px;
            z-index:100;
            padding:8px 0;
        }
        .paydrop-menu.open{display:block;}
        .paydrop-header{padding:6px 16px 4px;font-size:11px;color:var(--ink-soft);text-transform:uppercase;letter-spacing:.05em;}
        .paydrop .paydrop-item{
            display:flex;
            align-items:center;
            gap:10px;
            width:100%;
            height:auto;
            padding:10px 16px;
            background:transparent;
            border:none;
            border-radius:0;
            cursor:pointer;
            text-align:left;
            color:var(--coffee-900);
        }
        .paydrop .paydrop-item:hover{background:var(--sand-100);}
        .paydrop-icon{width:28px;height:28px;border-radius:6px;background:var(--sand-100);color:var(--coffee-700);display:flex;align-items:center;justify-content:center;flex-shrink:0;}
        .paydrop-label strong{display:block;font-size:13px;margin-bottom:1px;}
        .paydrop-label small{font-size:11px;color:var(--ink-soft);}
    </style>
    <div class="view-head">
        <div>
            <h2>Bookings</h2>
            <p class="sub">Track and manage every safari and tour booking request.</p>
        </div>
        <div class="view-actions" style="gap: 12px;">
            <div class="field" style="margin-bottom: 0;">
                <select id="bookingsCurrencySwitcher" onchange="updateBookingsCurrency(this.value)" style="min-width: 120px;">
                                        <option value="USD" selected>USD</option>
                                        <option value="EUR" >EUR</option>
                                        <option value="GBP" >GBP</option>
                                        <option value="JPY" >JPY</option>
                                        <option value="CAD" >CAD</option>
                                        <option value="AUD" >AUD</option>
                                        <option value="INR" >INR</option>
                                        <option value="TZS" >TZS</option>
                                        <option value="KES" >KES</option>
                                        <option value="UGX" >UGX</option>
                                        <option value="ZAR" >ZAR</option>
                                    </select>
            </div>
            <button class="btn btn-primary" onclick="openBookingModal()">+ New booking</button>
        </div>
    </div>

    <div class="table-card">
        <div class="table-toolbar">
            <div class="chip-filters" id="bookingFilterChips">
                <button class="chip active" data-filter="all" onclick="setBookingFilter('all')">All</button>
                <button class="chip" data-filter="Pending" onclick="setBookingFilter('Pending')">Pending</button>
                <button class="chip" data-filter="Confirmed" onclick="setBookingFilter('Confirmed')">Confirmed</button>
                <button class="chip" data-filter="Completed" onclick="setBookingFilter('Completed')">Completed</button>
                <button class="chip" data-filter="Cancelled" onclick="setBookingFilter('Cancelled')">Cancelled</button>
            </div>
            <div class="table-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" placeholder="Search guest or tour…" oninput="filterBookings(this.value)">
            </div>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Guest</th>
                        <th>Tour</th>
                        <th>Travel Date</th>
                        <th>Adults</th>
                        <th>Children</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="bookingsBody">
                                                                                <tr data-id="26" data-status="Pending" data-search="david ngungila davidngungila@gmail.com chemka hot springs">
                        <td>
                            <div class="cell-main">
                                <div>
                                    <div class="cell-title">David Ngungila</div>
                                    <div class="cell-sub">davidngungila@gmail.com</div>
                                                                        <div class="cell-sub mt-1">
                                        <span class="text-lg mr-1">🇹🇿</span>
                                        Tanzania (+255) +255622239304
                                    </div>
                                                                    </div>
                            </div>
                        </td>
                        <td>Chemka Hot Springs</td>
                        <td>Sep 14, 2026</td>
                        <td>1</td>
                        <td>0</td>
                        <td data-amount="228600.00" data-currency="TZS">
                            <div>TSh228,600</div>
                            <div style="font-size: 12px; color: var(--ink-soft);" class="converted-amount">
                                ~$90
                            </div>
                        </td>
                        <td>
                                                            <a href="https://tanzaniadailytoursandsafari.com/live/payments/7" style="text-decoration:none;">
                                    <span class="tag tag-gold">Pending</span>
                                </a>
                                                    </td>
                        <td><span class="tag tag-gold">Pending</span></td>
                        <td>
                            <div class="row-actions">
                                <button onclick="viewBooking(26)" title="View">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                                <button onclick="editBooking(26)" title="Edit">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4Z"></path>
                                    </svg>
                                </button>
                                <div class="paydrop">
                                    <button type="button" class="paydrop-toggle" onclick="togglePaydrop(event, this)" title="Send payment link">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                                            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                                        </svg>
                                    </button>
                                    <div class="paydrop-menu" data-paydrop-menu>
                                        <div class="paydrop-header">Payment link</div>
                                        <form action="https://tanzaniadailytoursandsafari.com/live/bookings/26/payment-link" method="POST" style="margin:0;padding:0;">
                                            <input type="hidden" name="_token" value="w3Vlfvw2Bh8VfqolGpMryy5mbgT3npZg5cr409mV" autocomplete="off">                                            <button type="submit" class="paydrop-item">
                                                <span class="paydrop-icon">✉</span>
                                                <span class="paydrop-label">
                                                    <strong>Send via email</strong>
                                                    <small>Customized link with system context &amp; colors</small>
                                                </span>
                                            </button>
                                        </form>
                                        <button type="button" class="paydrop-item" data-copy-link="26">
                                            <span class="paydrop-icon">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;">
                                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                                </svg>
                                            </span>
                                            <span class="paydrop-label">
                                                <strong>Copy payment link</strong>
                                                <small>Copy the raw URL to your clipboard</small>
                                            </span>
                                        </button>
                                    </div>
                                </div>
                                <form action="https://tanzaniadailytoursandsafari.com/live/bookings/26" method="POST" onsubmit="return confirm('Delete this booking?')">
                                    <input type="hidden" name="_token" value="w3Vlfvw2Bh8VfqolGpMryy5mbgT3npZg5cr409mV" autocomplete="off">                                    <input type="hidden" name="_method" value="DELETE">                                    <button class="danger" type="submit" title="Delete">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                                                            <tr data-id="19" data-status="Confirmed" data-search="epimack laurent epimacklaurent@gmail.com 4-day gombe chimpanzee trekking safari from dar es salaam">
                        <td>
                            <div class="cell-main">
                                <div>
                                    <div class="cell-title">Epimack Laurent</div>
                                    <div class="cell-sub">epimacklaurent@gmail.com</div>
                                                                        <div class="cell-sub mt-1">
                                        <span class="text-lg mr-1">🇹🇿</span>
                                        Tanzania (+255) +255755509750
                                    </div>
                                                                    </div>
                            </div>
                        </td>
                        <td>4-Day Gombe Chimpanzee Trekking Safari from Dar es Salaam</td>
                        <td>Sep 03, 2026</td>
                        <td>1</td>
                        <td>0</td>
                        <td data-amount="1850.00" data-currency="USD">
                            <div>$1,850</div>
                            <div style="font-size: 12px; color: var(--ink-soft);" class="converted-amount">
                                ~$1,850
                            </div>
                        </td>
                        <td>
                                                            <span style="font-size:12px;color:var(--ink-soft);">—</span>
                                                    </td>
                        <td><span class="tag tag-green">Confirmed</span></td>
                        <td>
                            <div class="row-actions">
                                <button onclick="viewBooking(19)" title="View">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                                <button onclick="editBooking(19)" title="Edit">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4Z"></path>
                                    </svg>
                                </button>
                                <div class="paydrop">
                                    <button type="button" class="paydrop-toggle" onclick="togglePaydrop(event, this)" title="Send payment link">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                                            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                                        </svg>
                                    </button>
                                    <div class="paydrop-menu" data-paydrop-menu>
                                        <div class="paydrop-header">Payment link</div>
                                        <form action="https://tanzaniadailytoursandsafari.com/live/bookings/19/payment-link" method="POST" style="margin:0;padding:0;">
                                            <input type="hidden" name="_token" value="w3Vlfvw2Bh8VfqolGpMryy5mbgT3npZg5cr409mV" autocomplete="off">                                            <button type="submit" class="paydrop-item">
                                                <span class="paydrop-icon">✉</span>
                                                <span class="paydrop-label">
                                                    <strong>Send via email</strong>
                                                    <small>Customized link with system context &amp; colors</small>
                                                </span>
                                            </button>
                                        </form>
                                        <button type="button" class="paydrop-item" data-copy-link="19">
                                            <span class="paydrop-icon">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;">
                                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                                </svg>
                                            </span>
                                            <span class="paydrop-label">
                                                <strong>Copy payment link</strong>
                                                <small>Copy the raw URL to your clipboard</small>
                                            </span>
                                        </button>
                                    </div>
                                </div>
                                <form action="https://tanzaniadailytoursandsafari.com/live/bookings/19" method="POST" onsubmit="return confirm('Delete this booking?')">
                                    <input type="hidden" name="_token" value="w3Vlfvw2Bh8VfqolGpMryy5mbgT3npZg5cr409mV" autocomplete="off">                                    <input type="hidden" name="_method" value="DELETE">                                    <button class="danger" type="submit" title="Delete">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                                                        </tbody>
            </table>
        </div>
            </div>
</div>

<div class="modal-backdrop" id="bookingModalBackdrop">
    <div class="modal">
        <div class="modal-head">
            <h3 id="bookingModalTitle">New booking</h3>
            <button class="modal-close" onclick="closeModal('bookingModalBackdrop')">✕</button>
        </div>
        <form id="bookingForm" action="https://tanzaniadailytoursandsafari.com/live/bookings" method="POST">
            <input type="hidden" name="_token" value="w3Vlfvw2Bh8VfqolGpMryy5mbgT3npZg5cr409mV" autocomplete="off">            <input type="hidden" id="bookingId" name="id">
            <input type="hidden" id="bookingMethod" name="_method" value="">
            <div class="modal-body">
                <div class="form-row">
                    <div class="field">
                        <label>Guest name</label>
                        <input type="text" id="bookingName" name="name" placeholder="e.g. Sarah Mitchell" required>
                    </div>
                    <div class="field">
                        <label>Guest email</label>
                        <input type="email" id="bookingEmail" name="email" placeholder="sarah@email.com" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="field">
                        <label>Country</label>
                        <select id="bookingCountryCode" name="country_code">
                                                        <option value="+93" data-flag="🇦🇫" >🇦🇫 Afghanistan (+93)</option>
                                                        <option value="+355" data-flag="🇦🇱" >🇦🇱 Albania (+355)</option>
                                                        <option value="+213" data-flag="🇩🇿" >🇩🇿 Algeria (+213)</option>
                                                        <option value="+1-684" data-flag="🇦🇸" >🇦🇸 American Samoa (+1-684)</option>
                                                        <option value="+376" data-flag="🇦🇩" >🇦🇩 Andorra (+376)</option>
                                                        <option value="+244" data-flag="🇦🇴" >🇦🇴 Angola (+244)</option>
                                                        <option value="+1-264" data-flag="🇦🇮" >🇦🇮 Anguilla (+1-264)</option>
                                                        <option value="+672" data-flag="🇦🇶" >🇦🇶 Antarctica (+672)</option>
                                                        <option value="+1-268" data-flag="🇦🇬" >🇦🇬 Antigua and Barbuda (+1-268)</option>
                                                        <option value="+54" data-flag="🇦🇷" >🇦🇷 Argentina (+54)</option>
                                                        <option value="+374" data-flag="🇦🇲" >🇦🇲 Armenia (+374)</option>
                                                        <option value="+297" data-flag="🇦🇼" >🇦🇼 Aruba (+297)</option>
                                                        <option value="+61" data-flag="🇦🇺" >🇦🇺 Australia (+61)</option>
                                                        <option value="+43" data-flag="🇦🇹" >🇦🇹 Austria (+43)</option>
                                                        <option value="+994" data-flag="🇦🇿" >🇦🇿 Azerbaijan (+994)</option>
                                                        <option value="+1-242" data-flag="🇧🇸" >🇧🇸 Bahamas (+1-242)</option>
                                                        <option value="+973" data-flag="🇧🇭" >🇧🇭 Bahrain (+973)</option>
                                                        <option value="+880" data-flag="🇧🇩" >🇧🇩 Bangladesh (+880)</option>
                                                        <option value="+1-246" data-flag="🇧🇧" >🇧🇧 Barbados (+1-246)</option>
                                                        <option value="+375" data-flag="🇧🇾" >🇧🇾 Belarus (+375)</option>
                                                        <option value="+32" data-flag="🇧🇪" >🇧🇪 Belgium (+32)</option>
                                                        <option value="+501" data-flag="🇧🇿" >🇧🇿 Belize (+501)</option>
                                                        <option value="+229" data-flag="🇧🇯" >🇧🇯 Benin (+229)</option>
                                                        <option value="+1-441" data-flag="🇧🇲" >🇧🇲 Bermuda (+1-441)</option>
                                                        <option value="+975" data-flag="🇧🇹" >🇧🇹 Bhutan (+975)</option>
                                                        <option value="+591" data-flag="🇧🇴" >🇧🇴 Bolivia (+591)</option>
                                                        <option value="+387" data-flag="🇧🇦" >🇧🇦 Bosnia and Herzegovina (+387)</option>
                                                        <option value="+267" data-flag="🇧🇼" >🇧🇼 Botswana (+267)</option>
                                                        <option value="+55" data-flag="🇧🇷" >🇧🇷 Brazil (+55)</option>
                                                        <option value="+246" data-flag="🇮🇴" >🇮🇴 British Indian Ocean Territory (+246)</option>
                                                        <option value="+1-284" data-flag="🇻🇬" >🇻🇬 British Virgin Islands (+1-284)</option>
                                                        <option value="+673" data-flag="🇧🇳" >🇧🇳 Brunei (+673)</option>
                                                        <option value="+359" data-flag="🇧🇬" >🇧🇬 Bulgaria (+359)</option>
                                                        <option value="+226" data-flag="🇧🇫" >🇧🇫 Burkina Faso (+226)</option>
                                                        <option value="+257" data-flag="🇧🇮" >🇧🇮 Burundi (+257)</option>
                                                        <option value="+855" data-flag="🇰🇭" >🇰🇭 Cambodia (+855)</option>
                                                        <option value="+237" data-flag="🇨🇲" >🇨🇲 Cameroon (+237)</option>
                                                        <option value="+1" data-flag="🇨🇦" >🇨🇦 Canada (+1)</option>
                                                        <option value="+238" data-flag="🇨🇻" >🇨🇻 Cape Verde (+238)</option>
                                                        <option value="+1-345" data-flag="🇰🇾" >🇰🇾 Cayman Islands (+1-345)</option>
                                                        <option value="+236" data-flag="🇨🇫" >🇨🇫 Central African Republic (+236)</option>
                                                        <option value="+235" data-flag="🇹🇩" >🇹🇩 Chad (+235)</option>
                                                        <option value="+56" data-flag="🇨🇱" >🇨🇱 Chile (+56)</option>
                                                        <option value="+86" data-flag="🇨🇳" >🇨🇳 China (+86)</option>
                                                        <option value="+61" data-flag="🇨🇽" >🇨🇽 Christmas Island (+61)</option>
                                                        <option value="+61" data-flag="🇨🇨" >🇨🇨 Cocos Islands (+61)</option>
                                                        <option value="+57" data-flag="🇨🇴" >🇨🇴 Colombia (+57)</option>
                                                        <option value="+269" data-flag="🇰🇲" >🇰🇲 Comoros (+269)</option>
                                                        <option value="+242" data-flag="🇨🇬" >🇨🇬 Congo (+242)</option>
                                                        <option value="+243" data-flag="🇨🇩" >🇨🇩 Congo (DRC) (+243)</option>
                                                        <option value="+682" data-flag="🇨🇰" >🇨🇰 Cook Islands (+682)</option>
                                                        <option value="+506" data-flag="🇨🇷" >🇨🇷 Costa Rica (+506)</option>
                                                        <option value="+385" data-flag="🇭🇷" >🇭🇷 Croatia (+385)</option>
                                                        <option value="+53" data-flag="🇨🇺" >🇨🇺 Cuba (+53)</option>
                                                        <option value="+599" data-flag="🇨🇼" >🇨🇼 Curacao (+599)</option>
                                                        <option value="+357" data-flag="🇨🇾" >🇨🇾 Cyprus (+357)</option>
                                                        <option value="+420" data-flag="🇨🇿" >🇨🇿 Czech Republic (+420)</option>
                                                        <option value="+45" data-flag="🇩🇰" >🇩🇰 Denmark (+45)</option>
                                                        <option value="+253" data-flag="🇩🇯" >🇩🇯 Djibouti (+253)</option>
                                                        <option value="+1-767" data-flag="🇩🇲" >🇩🇲 Dominica (+1-767)</option>
                                                        <option value="+1-809" data-flag="🇩🇴" >🇩🇴 Dominican Republic (+1-809)</option>
                                                        <option value="+670" data-flag="🇹🇱" >🇹🇱 East Timor (+670)</option>
                                                        <option value="+593" data-flag="🇪🇨" >🇪🇨 Ecuador (+593)</option>
                                                        <option value="+20" data-flag="🇪🇬" >🇪🇬 Egypt (+20)</option>
                                                        <option value="+503" data-flag="🇸🇻" >🇸🇻 El Salvador (+503)</option>
                                                        <option value="+240" data-flag="🇬🇶" >🇬🇶 Equatorial Guinea (+240)</option>
                                                        <option value="+291" data-flag="🇪🇷" >🇪🇷 Eritrea (+291)</option>
                                                        <option value="+372" data-flag="🇪🇪" >🇪🇪 Estonia (+372)</option>
                                                        <option value="+251" data-flag="🇪🇹" >🇪🇹 Ethiopia (+251)</option>
                                                        <option value="+500" data-flag="🇫🇰" >🇫🇰 Falkland Islands (+500)</option>
                                                        <option value="+298" data-flag="🇫🇴" >🇫🇴 Faroe Islands (+298)</option>
                                                        <option value="+679" data-flag="🇫🇯" >🇫🇯 Fiji (+679)</option>
                                                        <option value="+358" data-flag="🇫🇮" >🇫🇮 Finland (+358)</option>
                                                        <option value="+33" data-flag="🇫🇷" >🇫🇷 France (+33)</option>
                                                        <option value="+594" data-flag="🇬🇫" >🇬🇫 French Guiana (+594)</option>
                                                        <option value="+689" data-flag="🇵🇫" >🇵🇫 French Polynesia (+689)</option>
                                                        <option value="+241" data-flag="🇬🇦" >🇬🇦 Gabon (+241)</option>
                                                        <option value="+220" data-flag="🇬🇲" >🇬🇲 Gambia (+220)</option>
                                                        <option value="+995" data-flag="🇬🇪" >🇬🇪 Georgia (+995)</option>
                                                        <option value="+49" data-flag="🇩🇪" >🇩🇪 Germany (+49)</option>
                                                        <option value="+233" data-flag="🇬🇭" >🇬🇭 Ghana (+233)</option>
                                                        <option value="+350" data-flag="🇬🇮" >🇬🇮 Gibraltar (+350)</option>
                                                        <option value="+30" data-flag="🇬🇷" >🇬🇷 Greece (+30)</option>
                                                        <option value="+299" data-flag="🇬🇱" >🇬🇱 Greenland (+299)</option>
                                                        <option value="+1-473" data-flag="🇬🇩" >🇬🇩 Grenada (+1-473)</option>
                                                        <option value="+590" data-flag="🇬🇵" >🇬🇵 Guadeloupe (+590)</option>
                                                        <option value="+1-671" data-flag="🇬🇺" >🇬🇺 Guam (+1-671)</option>
                                                        <option value="+502" data-flag="🇬🇹" >🇬🇹 Guatemala (+502)</option>
                                                        <option value="+44" data-flag="🇬🇬" >🇬🇬 Guernsey (+44)</option>
                                                        <option value="+224" data-flag="🇬🇳" >🇬🇳 Guinea (+224)</option>
                                                        <option value="+245" data-flag="🇬🇼" >🇬🇼 Guinea-Bissau (+245)</option>
                                                        <option value="+592" data-flag="🇬🇾" >🇬🇾 Guyana (+592)</option>
                                                        <option value="+509" data-flag="🇭🇹" >🇭🇹 Haiti (+509)</option>
                                                        <option value="+504" data-flag="🇭🇳" >🇭🇳 Honduras (+504)</option>
                                                        <option value="+852" data-flag="🇭🇰" >🇭🇰 Hong Kong (+852)</option>
                                                        <option value="+36" data-flag="🇭🇺" >🇭🇺 Hungary (+36)</option>
                                                        <option value="+354" data-flag="🇮🇸" >🇮🇸 Iceland (+354)</option>
                                                        <option value="+91" data-flag="🇮🇳" >🇮🇳 India (+91)</option>
                                                        <option value="+62" data-flag="🇮🇩" >🇮🇩 Indonesia (+62)</option>
                                                        <option value="+98" data-flag="🇮🇷" >🇮🇷 Iran (+98)</option>
                                                        <option value="+964" data-flag="🇮🇶" >🇮🇶 Iraq (+964)</option>
                                                        <option value="+353" data-flag="🇮🇪" >🇮🇪 Ireland (+353)</option>
                                                        <option value="+44" data-flag="🇮🇲" >🇮🇲 Isle of Man (+44)</option>
                                                        <option value="+972" data-flag="🇮🇱" >🇮🇱 Israel (+972)</option>
                                                        <option value="+39" data-flag="🇮🇹" >🇮🇹 Italy (+39)</option>
                                                        <option value="+225" data-flag="🇨🇮" >🇨🇮 Ivory Coast (+225)</option>
                                                        <option value="+1-876" data-flag="🇯🇲" >🇯🇲 Jamaica (+1-876)</option>
                                                        <option value="+81" data-flag="🇯🇵" >🇯🇵 Japan (+81)</option>
                                                        <option value="+44" data-flag="🇯🇪" >🇯🇪 Jersey (+44)</option>
                                                        <option value="+962" data-flag="🇯🇴" >🇯🇴 Jordan (+962)</option>
                                                        <option value="+7" data-flag="🇰🇿" >🇰🇿 Kazakhstan (+7)</option>
                                                        <option value="+254" data-flag="🇰🇪" >🇰🇪 Kenya (+254)</option>
                                                        <option value="+686" data-flag="🇰🇮" >🇰🇮 Kiribati (+686)</option>
                                                        <option value="+965" data-flag="🇰🇼" >🇰🇼 Kuwait (+965)</option>
                                                        <option value="+996" data-flag="🇰🇬" >🇰🇬 Kyrgyzstan (+996)</option>
                                                        <option value="+856" data-flag="🇱🇦" >🇱🇦 Laos (+856)</option>
                                                        <option value="+371" data-flag="🇱🇻" >🇱🇻 Latvia (+371)</option>
                                                        <option value="+961" data-flag="🇱🇧" >🇱🇧 Lebanon (+961)</option>
                                                        <option value="+266" data-flag="🇱🇸" >🇱🇸 Lesotho (+266)</option>
                                                        <option value="+231" data-flag="🇱🇷" >🇱🇷 Liberia (+231)</option>
                                                        <option value="+218" data-flag="🇱🇾" >🇱🇾 Libya (+218)</option>
                                                        <option value="+423" data-flag="🇱🇮" >🇱🇮 Liechtenstein (+423)</option>
                                                        <option value="+370" data-flag="🇱🇹" >🇱🇹 Lithuania (+370)</option>
                                                        <option value="+352" data-flag="🇱🇺" >🇱🇺 Luxembourg (+352)</option>
                                                        <option value="+853" data-flag="🇲🇴" >🇲🇴 Macau (+853)</option>
                                                        <option value="+389" data-flag="🇲🇰" >🇲🇰 Macedonia (+389)</option>
                                                        <option value="+261" data-flag="🇲🇬" >🇲🇬 Madagascar (+261)</option>
                                                        <option value="+265" data-flag="🇲🇼" >🇲🇼 Malawi (+265)</option>
                                                        <option value="+60" data-flag="🇲🇾" >🇲🇾 Malaysia (+60)</option>
                                                        <option value="+960" data-flag="🇲🇻" >🇲🇻 Maldives (+960)</option>
                                                        <option value="+223" data-flag="🇲🇱" >🇲🇱 Mali (+223)</option>
                                                        <option value="+356" data-flag="🇲🇹" >🇲🇹 Malta (+356)</option>
                                                        <option value="+692" data-flag="🇲🇭" >🇲🇭 Marshall Islands (+692)</option>
                                                        <option value="+596" data-flag="🇲🇶" >🇲🇶 Martinique (+596)</option>
                                                        <option value="+222" data-flag="🇲🇷" >🇲🇷 Mauritania (+222)</option>
                                                        <option value="+230" data-flag="🇲🇺" >🇲🇺 Mauritius (+230)</option>
                                                        <option value="+262" data-flag="🇾🇹" >🇾🇹 Mayotte (+262)</option>
                                                        <option value="+52" data-flag="🇲🇽" >🇲🇽 Mexico (+52)</option>
                                                        <option value="+691" data-flag="🇫🇲" >🇫🇲 Micronesia (+691)</option>
                                                        <option value="+373" data-flag="🇲🇩" >🇲🇩 Moldova (+373)</option>
                                                        <option value="+377" data-flag="🇲🇨" >🇲🇨 Monaco (+377)</option>
                                                        <option value="+976" data-flag="🇲🇳" >🇲🇳 Mongolia (+976)</option>
                                                        <option value="+382" data-flag="🇲🇪" >🇲🇪 Montenegro (+382)</option>
                                                        <option value="+1-664" data-flag="🇲🇸" >🇲🇸 Montserrat (+1-664)</option>
                                                        <option value="+212" data-flag="🇲🇦" >🇲🇦 Morocco (+212)</option>
                                                        <option value="+258" data-flag="🇲🇿" >🇲🇿 Mozambique (+258)</option>
                                                        <option value="+95" data-flag="🇲🇲" >🇲🇲 Myanmar (+95)</option>
                                                        <option value="+264" data-flag="🇳🇦" >🇳🇦 Namibia (+264)</option>
                                                        <option value="+674" data-flag="🇳🇷" >🇳🇷 Nauru (+674)</option>
                                                        <option value="+977" data-flag="🇳🇵" >🇳🇵 Nepal (+977)</option>
                                                        <option value="+31" data-flag="🇳🇱" >🇳🇱 Netherlands (+31)</option>
                                                        <option value="+599" data-flag="🇧🇶" >🇧🇶 Netherlands Antilles (+599)</option>
                                                        <option value="+687" data-flag="🇳🇨" >🇳🇨 New Caledonia (+687)</option>
                                                        <option value="+64" data-flag="🇳🇿" >🇳🇿 New Zealand (+64)</option>
                                                        <option value="+505" data-flag="🇳🇮" >🇳🇮 Nicaragua (+505)</option>
                                                        <option value="+227" data-flag="🇳🇪" >🇳🇪 Niger (+227)</option>
                                                        <option value="+234" data-flag="🇳🇬" >🇳🇬 Nigeria (+234)</option>
                                                        <option value="+683" data-flag="🇳🇺" >🇳🇺 Niue (+683)</option>
                                                        <option value="+1-670" data-flag="🇲🇵" >🇲🇵 Northern Mariana Islands (+1-670)</option>
                                                        <option value="+47" data-flag="🇳🇴" >🇳🇴 Norway (+47)</option>
                                                        <option value="+968" data-flag="🇴🇲" >🇴🇲 Oman (+968)</option>
                                                        <option value="+92" data-flag="🇵🇰" >🇵🇰 Pakistan (+92)</option>
                                                        <option value="+680" data-flag="🇵🇼" >🇵🇼 Palau (+680)</option>
                                                        <option value="+970" data-flag="🇵🇸" >🇵🇸 Palestine (+970)</option>
                                                        <option value="+507" data-flag="🇵🇦" >🇵🇦 Panama (+507)</option>
                                                        <option value="+675" data-flag="🇵🇬" >🇵🇬 Papua New Guinea (+675)</option>
                                                        <option value="+595" data-flag="🇵🇾" >🇵🇾 Paraguay (+595)</option>
                                                        <option value="+51" data-flag="🇵🇪" >🇵🇪 Peru (+51)</option>
                                                        <option value="+63" data-flag="🇵🇭" >🇵🇭 Philippines (+63)</option>
                                                        <option value="+48" data-flag="🇵🇱" >🇵🇱 Poland (+48)</option>
                                                        <option value="+351" data-flag="🇵🇹" >🇵🇹 Portugal (+351)</option>
                                                        <option value="+1-787" data-flag="🇵🇷" >🇵🇷 Puerto Rico (+1-787)</option>
                                                        <option value="+974" data-flag="🇶🇦" >🇶🇦 Qatar (+974)</option>
                                                        <option value="+262" data-flag="🇷🇪" >🇷🇪 Reunion (+262)</option>
                                                        <option value="+40" data-flag="🇷🇴" >🇷🇴 Romania (+40)</option>
                                                        <option value="+7" data-flag="🇷🇺" >🇷🇺 Russia (+7)</option>
                                                        <option value="+250" data-flag="🇷🇼" >🇷🇼 Rwanda (+250)</option>
                                                        <option value="+590" data-flag="🇧🇱" >🇧🇱 Saint Barthelemy (+590)</option>
                                                        <option value="+290" data-flag="🇸🇭" >🇸🇭 Saint Helena (+290)</option>
                                                        <option value="+1-869" data-flag="🇰🇳" >🇰🇳 Saint Kitts and Nevis (+1-869)</option>
                                                        <option value="+1-758" data-flag="🇱🇨" >🇱🇨 Saint Lucia (+1-758)</option>
                                                        <option value="+590" data-flag="🇲🇫" >🇲🇫 Saint Martin (+590)</option>
                                                        <option value="+508" data-flag="🇵🇲" >🇵🇲 Saint Pierre and Miquelon (+508)</option>
                                                        <option value="+1-784" data-flag="🇻🇨" >🇻🇨 Saint Vincent and the Grenadines (+1-784)</option>
                                                        <option value="+685" data-flag="🇼🇸" >🇼🇸 Samoa (+685)</option>
                                                        <option value="+378" data-flag="🇸🇲" >🇸🇲 San Marino (+378)</option>
                                                        <option value="+239" data-flag="🇸🇹" >🇸🇹 Sao Tome and Principe (+239)</option>
                                                        <option value="+966" data-flag="🇸🇦" >🇸🇦 Saudi Arabia (+966)</option>
                                                        <option value="+221" data-flag="🇸🇳" >🇸🇳 Senegal (+221)</option>
                                                        <option value="+381" data-flag="🇷🇸" >🇷🇸 Serbia (+381)</option>
                                                        <option value="+248" data-flag="🇸🇨" >🇸🇨 Seychelles (+248)</option>
                                                        <option value="+232" data-flag="🇸🇱" >🇸🇱 Sierra Leone (+232)</option>
                                                        <option value="+65" data-flag="🇸🇬" >🇸🇬 Singapore (+65)</option>
                                                        <option value="+1-721" data-flag="🇸🇽" >🇸🇽 Sint Maarten (+1-721)</option>
                                                        <option value="+421" data-flag="🇸🇰" >🇸🇰 Slovakia (+421)</option>
                                                        <option value="+386" data-flag="🇸🇮" >🇸🇮 Slovenia (+386)</option>
                                                        <option value="+677" data-flag="🇸🇧" >🇸🇧 Solomon Islands (+677)</option>
                                                        <option value="+252" data-flag="🇸🇴" >🇸🇴 Somalia (+252)</option>
                                                        <option value="+27" data-flag="🇿🇦" >🇿🇦 South Africa (+27)</option>
                                                        <option value="+82" data-flag="🇰🇷" >🇰🇷 South Korea (+82)</option>
                                                        <option value="+211" data-flag="🇸🇸" >🇸🇸 South Sudan (+211)</option>
                                                        <option value="+34" data-flag="🇪🇸" >🇪🇸 Spain (+34)</option>
                                                        <option value="+94" data-flag="🇱🇰" >🇱🇰 Sri Lanka (+94)</option>
                                                        <option value="+249" data-flag="🇸🇩" >🇸🇩 Sudan (+249)</option>
                                                        <option value="+597" data-flag="🇸🇷" >🇸🇷 Suriname (+597)</option>
                                                        <option value="+268" data-flag="🇸🇿" >🇸🇿 Swaziland (+268)</option>
                                                        <option value="+46" data-flag="🇸🇪" >🇸🇪 Sweden (+46)</option>
                                                        <option value="+41" data-flag="🇨🇭" >🇨🇭 Switzerland (+41)</option>
                                                        <option value="+963" data-flag="🇸🇾" >🇸🇾 Syria (+963)</option>
                                                        <option value="+886" data-flag="🇹🇼" >🇹🇼 Taiwan (+886)</option>
                                                        <option value="+992" data-flag="🇹🇯" >🇹🇯 Tajikistan (+992)</option>
                                                        <option value="+255" data-flag="🇹🇿" selected>🇹🇿 Tanzania (+255)</option>
                                                        <option value="+66" data-flag="🇹🇭" >🇹🇭 Thailand (+66)</option>
                                                        <option value="+228" data-flag="🇹🇬" >🇹🇬 Togo (+228)</option>
                                                        <option value="+690" data-flag="🇹🇰" >🇹🇰 Tokelau (+690)</option>
                                                        <option value="+676" data-flag="🇹🇴" >🇹🇴 Tonga (+676)</option>
                                                        <option value="+1-868" data-flag="🇹🇹" >🇹🇹 Trinidad and Tobago (+1-868)</option>
                                                        <option value="+216" data-flag="🇹🇳" >🇹🇳 Tunisia (+216)</option>
                                                        <option value="+90" data-flag="🇹🇷" >🇹🇷 Turkey (+90)</option>
                                                        <option value="+993" data-flag="🇹🇲" >🇹🇲 Turkmenistan (+993)</option>
                                                        <option value="+1-649" data-flag="🇹🇨" >🇹🇨 Turks and Caicos Islands (+1-649)</option>
                                                        <option value="+688" data-flag="🇹🇻" >🇹🇻 Tuvalu (+688)</option>
                                                        <option value="+256" data-flag="🇺🇬" >🇺🇬 Uganda (+256)</option>
                                                        <option value="+380" data-flag="🇺🇦" >🇺🇦 Ukraine (+380)</option>
                                                        <option value="+971" data-flag="🇦🇪" >🇦🇪 United Arab Emirates (+971)</option>
                                                        <option value="+44" data-flag="🇬🇧" >🇬🇧 United Kingdom (+44)</option>
                                                        <option value="+1" data-flag="🇺🇸" >🇺🇸 United States (+1)</option>
                                                        <option value="+598" data-flag="🇺🇾" >🇺🇾 Uruguay (+598)</option>
                                                        <option value="+998" data-flag="🇺🇿" >🇺🇿 Uzbekistan (+998)</option>
                                                        <option value="+678" data-flag="🇻🇺" >🇻🇺 Vanuatu (+678)</option>
                                                        <option value="+379" data-flag="🇻🇦" >🇻🇦 Vatican (+379)</option>
                                                        <option value="+58" data-flag="🇻🇪" >🇻🇪 Venezuela (+58)</option>
                                                        <option value="+84" data-flag="🇻🇳" >🇻🇳 Vietnam (+84)</option>
                                                        <option value="+1-340" data-flag="🇻🇮" >🇻🇮 Virgin Islands (+1-340)</option>
                                                        <option value="+681" data-flag="🇼🇫" >🇼🇫 Wallis and Futuna (+681)</option>
                                                        <option value="+967" data-flag="🇾🇪" >🇾🇪 Yemen (+967)</option>
                                                        <option value="+260" data-flag="🇿🇲" >🇿🇲 Zambia (+260)</option>
                                                        <option value="+263" data-flag="🇿🇼" >🇿🇼 Zimbabwe (+263)</option>
                                                    </select>
                    </div>
                    <div class="field">
                        <label>Phone Number</label>
                        <input type="text" id="bookingPhone" name="phone_number" placeholder="712 345 678">
                    </div>
                </div>
                <div class="field">
                    <label>Tour / destination</label>
                    <select id="bookingTour" name="tour_name">
                                                <option value="Materuni Waterfall &amp; Coffee Tour">Materuni Waterfall &amp; Coffee Tour</option>
                                                <option value="Chemka Hot Springs">Chemka Hot Springs</option>
                                                <option value="Marangu Cultural Tour">Marangu Cultural Tour</option>
                                                <option value="Kilimanjaro Day Hike">Kilimanjaro Day Hike</option>
                                                <option value="3-Day Classic Tarangire &amp; Ngorongoro Crater Safari">3-Day Classic Tarangire &amp; Ngorongoro Crater Safari</option>
                                                <option value="Climb Kilimanjaro 5 Days Marangu Route">Climb Kilimanjaro 5 Days Marangu Route</option>
                                                <option value="Climb Kilimanjaro 7 Days Machame Route">Climb Kilimanjaro 7 Days Machame Route</option>
                                                <option value="5 Day Kilimanjaro Marangu Route and 2 Day Tanzania Safari">5 Day Kilimanjaro Marangu Route and 2 Day Tanzania Safari</option>
                                                <option value="The Best 8 Days Kilimanjaro Lemosho Route">The Best 8 Days Kilimanjaro Lemosho Route</option>
                                                <option value="Mount Kilimanjaro Climb 6 Days Marangu Route">Mount Kilimanjaro Climb 6 Days Marangu Route</option>
                                                <option value="Mount Kilimanjaro Climb 6 Days Umbwe Route">Mount Kilimanjaro Climb 6 Days Umbwe Route</option>
                                                <option value="Mount Kilimanjaro Climb 6 Days Rongai Route">Mount Kilimanjaro Climb 6 Days Rongai Route</option>
                                                <option value="6 Day Climb Kilimanjaro Marangu Route and 3 Day Tanzania Safari">6 Day Climb Kilimanjaro Marangu Route and 3 Day Tanzania Safari</option>
                                                <option value="7-Days Kilimanjaro Climb (Machame Route) &amp; 4-Days Tanzania Safari">7-Days Kilimanjaro Climb (Machame Route) &amp; 4-Days Tanzania Safari</option>
                                                <option value="5 Days Exotic Zanzibar Island Getaway &amp; Marine Safari">5 Days Exotic Zanzibar Island Getaway &amp; Marine Safari</option>
                                                <option value="Serengeti &amp; Ngorongoro Crater Safari">Serengeti &amp; Ngorongoro Crater Safari</option>
                                                <option value="Mount Kilimanjaro Climb 6 Days Machame Route">Mount Kilimanjaro Climb 6 Days Machame Route</option>
                                                <option value="Mount Kilimanjaro Climb 7 Days Rongai Route">Mount Kilimanjaro Climb 7 Days Rongai Route</option>
                                                <option value="Mount Kilimanjaro Climb 7 Days Lemosho Route">Mount Kilimanjaro Climb 7 Days Lemosho Route</option>
                                                <option value="Climb Kilimanjaro 7 Days Lemosho Route">Climb Kilimanjaro 7 Days Lemosho Route</option>
                                                <option value="Climb Kilimanjaro 6 Days Rongai Route">Climb Kilimanjaro 6 Days Rongai Route</option>
                                                <option value="4 Days Mount Meru Climbing">4 Days Mount Meru Climbing</option>
                                                <option value="Mount Meru 3 Days">Mount Meru 3 Days</option>
                                                <option value="Oldoinyo Lengai Trekking">Oldoinyo Lengai Trekking</option>
                                                <option value="Saanane Island National Park Mwanza Day Tours">Saanane Island National Park Mwanza Day Tours</option>
                                                <option value="Tanzania Culture Experience">Tanzania Culture Experience</option>
                                                <option value="Ngorongoro Day Tour">Ngorongoro Day Tour</option>
                                                <option value="Lake Manyara Day Tour">Lake Manyara Day Tour</option>
                                                <option value="Tarangire Day Tour">Tarangire Day Tour</option>
                                                <option value="4-Day Private Tanzania Safari &amp; Maasai Cultural Tour">4-Day Private Tanzania Safari &amp; Maasai Cultural Tour</option>
                                                <option value="8-Day Serengeti Northern Migration Safari Adventure">8-Day Serengeti Northern Migration Safari Adventure</option>
                                                <option value="12 Days Northern Migration Safari and Ol Doinyo Lengai">12 Days Northern Migration Safari and Ol Doinyo Lengai</option>
                                                <option value="12-Day Ultimate Tanzania Wildlife Safari Expedition">12-Day Ultimate Tanzania Wildlife Safari Expedition</option>
                                                <option value="11-Day Tanzania Safari &amp; Cultural Discovery">11-Day Tanzania Safari &amp; Cultural Discovery</option>
                                                <option value="7-Day Ultimate Tanzania Wildlife Safari Experience &amp; Cultural Immersion">7-Day Ultimate Tanzania Wildlife Safari Experience &amp; Cultural Immersion</option>
                                                <option value="6-Day Tanzania Northern Circuit Safari: Big Five &amp; Cultural Immersion">6-Day Tanzania Northern Circuit Safari: Big Five &amp; Cultural Immersion</option>
                                                <option value="3-Day Fly-In Selous Game Reserve (Nyerere National Park) Safari">3-Day Fly-In Selous Game Reserve (Nyerere National Park) Safari</option>
                                                <option value="3-Day Fly-In Serengeti National Park Safari from Dar es Salaam">3-Day Fly-In Serengeti National Park Safari from Dar es Salaam</option>
                                                <option value="Ultimate 6-Day Tanzania Northern Circuit Wildlife Tour.">Ultimate 6-Day Tanzania Northern Circuit Wildlife Tour.</option>
                                                <option value="Best of 5-Day Fly-In Tanzania Safari: Serengeti Big Cats &amp; Ngorongoro Crater.">Best of 5-Day Fly-In Tanzania Safari: Serengeti Big Cats &amp; Ngorongoro Crater.</option>
                                                <option value="3-Day Fly-In Safari from Zanzibar to Mikumi National Park">3-Day Fly-In Safari from Zanzibar to Mikumi National Park</option>
                                                <option value="Ultimate 5-Day Tanzania Wildlife Safari: Tarangire, Central Serengeti &amp; Ngorongoro Crater">Ultimate 5-Day Tanzania Wildlife Safari: Tarangire, Central Serengeti &amp; Ngorongoro Crater</option>
                                                <option value="4-Day Gombe Chimpanzee Trekking Safari from Dar es Salaam">4-Day Gombe Chimpanzee Trekking Safari from Dar es Salaam</option>
                                                <option value="12-Day Tanzania Safari &amp; Zanzibar Beach Holiday">12-Day Tanzania Safari &amp; Zanzibar Beach Holiday</option>
                                                <option value="The Best 3-Day Tanzania Safari Tour: Tarangire, Central Serengeti &amp; Ngorongoro Crater.">The Best 3-Day Tanzania Safari Tour: Tarangire, Central Serengeti &amp; Ngorongoro Crater.</option>
                                                <option value="6-Day Cultural &amp; Wildlife Safari in Tanzania: Bushmen, Big Cats &amp; The Ngorongoro Crater">6-Day Cultural &amp; Wildlife Safari in Tanzania: Bushmen, Big Cats &amp; The Ngorongoro Crater</option>
                                                <option value="8-Day Ultimate Guide to Great Migration Safaris: Tarangire, Serengeti Herds &amp; Ngorongoro Crater">8-Day Ultimate Guide to Great Migration Safaris: Tarangire, Serengeti Herds &amp; Ngorongoro Crater</option>
                                                <option value="Ultimate 8-Day Tanzania Family Safari Experiences: Wildlife Wonders &amp; Junior Adventure.">Ultimate 8-Day Tanzania Family Safari Experiences: Wildlife Wonders &amp; Junior Adventure.</option>
                                                <option value="8-Day Romantic Tanzania Safaris for Honeymooners: Sunset Sundowners, Serengeti Big Cats &amp; Ngorongoro Wonders.">8-Day Romantic Tanzania Safaris for Honeymooners: Sunset Sundowners, Serengeti Big Cats &amp; Ngorongoro Wonders.</option>
                                                <option value="3-Days Premium Camping Safari Adventures">3-Days Premium Camping Safari Adventures</option>
                                                <option value="4 Days Exclusive Camping Safaris in Tanzania">4 Days Exclusive Camping Safaris in Tanzania</option>
                                                <option value="The Best 2 Days Mid-Range Safaris in Tanzania">The Best 2 Days Mid-Range Safaris in Tanzania</option>
                                                <option value="3-Days Premium Camping Safari Adventures">3-Days Premium Camping Safari Adventures</option>
                                                <option value="Explore 4 Days Ndutu the Great Migration Season">Explore 4 Days Ndutu the Great Migration Season</option>
                                                <option value="6 Days Ultimate Wilderness Camping Safari">6 Days Ultimate Wilderness Camping Safari</option>
                                                <option value="1-Day VIP Serval Wildlife Sanctuary Experience">1-Day VIP Serval Wildlife Sanctuary Experience</option>
                                                <option value="4-Day Tanzania Safari Adventure">4-Day Tanzania Safari Adventure</option>
                                                <option value="5 Days Timeless Romance: The Ultimate Tanzanian Honeymoon Safari">5 Days Timeless Romance: The Ultimate Tanzanian Honeymoon Safari</option>
                                                <option value="1-Day Mount Meru Forest &amp; Arusha National Park Walking Safari">1-Day Mount Meru Forest &amp; Arusha National Park Walking Safari</option>
                                                <option value="3-Day Zanzibar Island Highlights Getaway">3-Day Zanzibar Island Highlights Getaway</option>
                                                <option value="6-Day Luxury Tanzania Family Wildlife &amp; Wilderness Safari">6-Day Luxury Tanzania Family Wildlife &amp; Wilderness Safari</option>
                                                <option value="1-Day Horseback Wildlife Safari Experience">1-Day Horseback Wildlife Safari Experience</option>
                                            </select>
                </div>
                <div class="form-row">
                    <div class="field">
                        <label>Travel date</label>
                        <input type="date" id="bookingDate" name="travel_date">
                    </div>
                </div>
                <div class="form-row">
                    <div class="field">
                        <label>Number of adults</label>
                        <input type="number" id="bookingAdults" name="adults" value="1" min="1">
                    </div>
                    <div class="field">
                        <label>Number of children</label>
                        <input type="number" id="bookingChildren" name="children" value="0" min="0">
                    </div>
                </div>
                <div class="form-row">
                    <div class="field">
                        <label>Total amount (USD)</label>
                        <input type="number" id="bookingAmount" name="amount" placeholder="80">
                    </div>
                    <div class="field">
                        <label>Status</label>
                        <select id="bookingStatus" name="status">
                            <option>Pending</option>
                            <option>Confirmed</option>
                            <option>Completed</option>
                            <option>Cancelled</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" onclick="closeModal('bookingModalBackdrop')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save booking</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-backdrop" id="viewBookingModalBackdrop">
    <div class="modal">
        <div class="modal-head">
            <h3>View Booking</h3>
            <button class="modal-close" onclick="closeModal('viewBookingModalBackdrop')">✕</button>
        </div>
        <div class="modal-body" id="viewBookingContent">
        </div>
        <div class="modal-foot">
            <button type="button" class="btn btn-ghost" onclick="closeModal('viewBookingModalBackdrop')">Close</button>
        </div>
    </div>
</div>

<script>
const bookingsData = [{"id":26,"destination_id":2,"tour_name":"Chemka Hot Springs","base_price":"90.00","currency":"TZS","travel_date":"2026-09-14","adults":1,"children":0,"email":"davidngungila@gmail.com","country_code":"+255","phone_number":"+255622239304","total_price":"228600.00","created_at":"2026-09-13T12:49:37.000000Z","updated_at":"2026-09-13T12:49:37.000000Z","name":"David Ngungila","guests":null,"amount":null,"status":"Pending","latest_payment":{"id":7,"booking_id":26,"merchant_reference":"TDTS-38A06363","order_tracking_id":null,"redirect_url":null,"payment_mode":"deposit","deposit_percentage":30,"amount":"68580.00","requested_amount":"228600.00","currency":"TZS","status":"pending","provider":"pesapal","payment_method":null,"description":"Chemka Hot Springs \u2014 booking TDTS-00026","customer_name":"David Ngungila","customer_email":"davidngungila@gmail.com","customer_phone":"+255 +255622239304","billing_address":{"last_name":"Ngungila","first_name":"David","phone_number":"+255622239304","email_address":"davidngungila@gmail.com"},"callback_data":null,"ipn_data":null,"raw_request":{"id":"TDTS-38A06363","amount":68580,"currency":"TZS","description":"Chemka Hot Springs \u2014 booking TDTS-00026","callback_url":"https:\/\/www.tanzaniadailytoursandsafari.com\/payments\/callback","redirect_mode":"TOP_WINDOW","billing_address":{"city":"","state":"","line_1":"","line_2":"","zip_code":"","last_name":"Ngungila","first_name":"David","middle_name":"","postal_code":"","country_code":"TZ","phone_number":"+255+255622239304","email_address":"davidngungila@gmail.com"},"notification_id":"5696b8ff-5eef-4d20-a25b-d9e8ce7f3ca4","cancellation_url":"https:\/\/www.tanzaniadailytoursandsafari.com\/payments\/cancelled"},"raw_response":{"submit_order":{"error":{"code":"amount_exceeds_default_limit","message":"Transaction amount exceeds limit.Contact support for assistance","error_type":"contractual_error"},"status":"500"}},"paid_at":null,"created_at":"2026-09-13T12:49:37.000000Z","updated_at":"2026-09-13T17:17:02.000000Z"}},{"id":19,"destination_id":50,"tour_name":"4-Day Gombe Chimpanzee Trekking Safari from Dar es Salaam","base_price":"1850.00","currency":"USD","travel_date":"2026-09-03","adults":1,"children":0,"email":"epimacklaurent@gmail.com","country_code":"+255","phone_number":"+255755509750","total_price":"1850.00","created_at":"2026-08-31T13:10:15.000000Z","updated_at":"2026-08-31T18:18:10.000000Z","name":"Epimack Laurent","guests":1,"amount":"1850.00","status":"Confirmed","latest_payment":null}];
const exchangeRates = {"USD":1,"EUR":0.92,"GBP":0.79,"JPY":151,"CAD":1.36,"AUD":1.53,"INR":83,"TZS":2540,"KES":130.5,"UGX":3800,"ZAR":18.5};
const currencySymbols = {"USD":"$","EUR":"\u20ac","GBP":"\u00a3","JPY":"\u00a5","CAD":"C$","AUD":"A$","INR":"\u20b9","TZS":"TSh","KES":"KSh","UGX":"USh","ZAR":"R"};
let currentBookingFilter = 'all';
let currentBookingSearch = '';

function setBookingFilter(filter) {
    currentBookingFilter = filter;
    document.querySelectorAll('#bookingFilterChips .chip').forEach(c => c.classList.toggle('active', c.dataset.filter === filter));
    renderFilteredBookings();
}

function filterBookings(search) {
    currentBookingSearch = search.toLowerCase();
    renderFilteredBookings();
}

function renderFilteredBookings() {
    document.querySelectorAll('#bookingsBody tr').forEach(tr => {
        if (!tr.dataset.id) return;
        let match = true;
        if (currentBookingFilter !== 'all') {
            match = tr.dataset.status === currentBookingFilter;
        }
        if (currentBookingSearch) {
            match = match && tr.dataset.search.includes(currentBookingSearch);
        }
        tr.style.display = match ? 'table-row' : 'none';
    });
}

function openBookingModal(id = null) {
    const title = document.getElementById('bookingModalTitle');
    title.textContent = id ? 'Edit booking' : 'New booking';
    const form = document.getElementById('bookingForm');
    if (id) {
        const booking = bookingsData.find(b => b.id === id);
        if (!booking) return;
        form.action = "/live/bookings/" + id;
        document.getElementById('bookingMethod').value = 'PUT';
        document.getElementById('bookingId').value = booking.id;
        document.getElementById('bookingName').value = booking.name ?? booking.email;
        document.getElementById('bookingEmail').value = booking.email;
        document.getElementById('bookingCountryCode').value = booking.country_code;
        document.getElementById('bookingPhone').value = booking.phone_number;
        document.getElementById('bookingTour').value = booking.tour_name;
        document.getElementById('bookingDate').value = booking.travel_date;
        document.getElementById('bookingAdults').value = booking.adults ?? (booking.guests ?? 0);
        document.getElementById('bookingChildren').value = booking.children ?? 0;
        document.getElementById('bookingAmount').value = booking.amount ?? booking.total_price;
        document.getElementById('bookingStatus').value = booking.status;
    } else {
        form.action = "https://tanzaniadailytoursandsafari.com/live/bookings";
        document.getElementById('bookingMethod').value = '';
        document.getElementById('bookingId').value = '';
        document.getElementById('bookingName').value = '';
        document.getElementById('bookingEmail').value = '';
        document.getElementById('bookingCountryCode').value = '+255';
        document.getElementById('bookingPhone').value = '';
        document.getElementById('bookingTour').selectedIndex = 0;
        document.getElementById('bookingDate').value = '';
        document.getElementById('bookingAdults').value = 1;
        document.getElementById('bookingChildren').value = 0;
        document.getElementById('bookingAmount').value = '';
        document.getElementById('bookingStatus').value = 'Pending';
    }
    openModal('bookingModalBackdrop');
}

function editBooking(id) {
    openBookingModal(id);
}

function formatCurrency(amount, currency) {
    const symbol = currencySymbols[currency] || '$';
    return symbol + amount.toLocaleString('en-US', { maximumFractionDigits: 0 });
}

function convertCurrency(amount, fromCurrency, toCurrency) {
    const fromRate = exchangeRates[fromCurrency] || 1;
    const toRate = exchangeRates[toCurrency] || 1;
    const amountInUSD = amount / fromRate;
    return amountInUSD * toRate;
}

function updateBookingsCurrency(currency) {
    // Save to session via AJAX
    fetch("/live/currency-switch", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ currency: currency })
    });

    // Update all converted amounts
    document.querySelectorAll('#bookingsBody tr').forEach(tr => {
        if (!tr.dataset.id) return;
        const amountCell = tr.querySelector('[data-amount]');
        if (!amountCell) return;
        
        const amount = parseFloat(amountCell.dataset.amount);
        const fromCurrency = amountCell.dataset.currency;
        const fromRate = exchangeRates[fromCurrency] || 1;
        const toRate = exchangeRates[currency] || 1;
        
        const converted = (amount / fromRate) * toRate;
        const symbol = currencySymbols[currency] || '$';
        
        const convertedDiv = amountCell.querySelector('.converted-amount');
        if (convertedDiv) {
            convertedDiv.textContent = '~' + symbol + converted.toLocaleString('en-US', { maximumFractionDigits: 0 });
        }
    });
}

function viewBooking(id) {
    const booking = bookingsData.find(b => b.id === id);
    if (!booking) return;

    const countries = [{"code":"+93","name":"Afghanistan","flag":"\ud83c\udde6\ud83c\uddeb"},{"code":"+355","name":"Albania","flag":"\ud83c\udde6\ud83c\uddf1"},{"code":"+213","name":"Algeria","flag":"\ud83c\udde9\ud83c\uddff"},{"code":"+1-684","name":"American Samoa","flag":"\ud83c\udde6\ud83c\uddf8"},{"code":"+376","name":"Andorra","flag":"\ud83c\udde6\ud83c\udde9"},{"code":"+244","name":"Angola","flag":"\ud83c\udde6\ud83c\uddf4"},{"code":"+1-264","name":"Anguilla","flag":"\ud83c\udde6\ud83c\uddee"},{"code":"+672","name":"Antarctica","flag":"\ud83c\udde6\ud83c\uddf6"},{"code":"+1-268","name":"Antigua and Barbuda","flag":"\ud83c\udde6\ud83c\uddec"},{"code":"+54","name":"Argentina","flag":"\ud83c\udde6\ud83c\uddf7"},{"code":"+374","name":"Armenia","flag":"\ud83c\udde6\ud83c\uddf2"},{"code":"+297","name":"Aruba","flag":"\ud83c\udde6\ud83c\uddfc"},{"code":"+61","name":"Australia","flag":"\ud83c\udde6\ud83c\uddfa"},{"code":"+43","name":"Austria","flag":"\ud83c\udde6\ud83c\uddf9"},{"code":"+994","name":"Azerbaijan","flag":"\ud83c\udde6\ud83c\uddff"},{"code":"+1-242","name":"Bahamas","flag":"\ud83c\udde7\ud83c\uddf8"},{"code":"+973","name":"Bahrain","flag":"\ud83c\udde7\ud83c\udded"},{"code":"+880","name":"Bangladesh","flag":"\ud83c\udde7\ud83c\udde9"},{"code":"+1-246","name":"Barbados","flag":"\ud83c\udde7\ud83c\udde7"},{"code":"+375","name":"Belarus","flag":"\ud83c\udde7\ud83c\uddfe"},{"code":"+32","name":"Belgium","flag":"\ud83c\udde7\ud83c\uddea"},{"code":"+501","name":"Belize","flag":"\ud83c\udde7\ud83c\uddff"},{"code":"+229","name":"Benin","flag":"\ud83c\udde7\ud83c\uddef"},{"code":"+1-441","name":"Bermuda","flag":"\ud83c\udde7\ud83c\uddf2"},{"code":"+975","name":"Bhutan","flag":"\ud83c\udde7\ud83c\uddf9"},{"code":"+591","name":"Bolivia","flag":"\ud83c\udde7\ud83c\uddf4"},{"code":"+387","name":"Bosnia and Herzegovina","flag":"\ud83c\udde7\ud83c\udde6"},{"code":"+267","name":"Botswana","flag":"\ud83c\udde7\ud83c\uddfc"},{"code":"+55","name":"Brazil","flag":"\ud83c\udde7\ud83c\uddf7"},{"code":"+246","name":"British Indian Ocean Territory","flag":"\ud83c\uddee\ud83c\uddf4"},{"code":"+1-284","name":"British Virgin Islands","flag":"\ud83c\uddfb\ud83c\uddec"},{"code":"+673","name":"Brunei","flag":"\ud83c\udde7\ud83c\uddf3"},{"code":"+359","name":"Bulgaria","flag":"\ud83c\udde7\ud83c\uddec"},{"code":"+226","name":"Burkina Faso","flag":"\ud83c\udde7\ud83c\uddeb"},{"code":"+257","name":"Burundi","flag":"\ud83c\udde7\ud83c\uddee"},{"code":"+855","name":"Cambodia","flag":"\ud83c\uddf0\ud83c\udded"},{"code":"+237","name":"Cameroon","flag":"\ud83c\udde8\ud83c\uddf2"},{"code":"+1","name":"Canada","flag":"\ud83c\udde8\ud83c\udde6"},{"code":"+238","name":"Cape Verde","flag":"\ud83c\udde8\ud83c\uddfb"},{"code":"+1-345","name":"Cayman Islands","flag":"\ud83c\uddf0\ud83c\uddfe"},{"code":"+236","name":"Central African Republic","flag":"\ud83c\udde8\ud83c\uddeb"},{"code":"+235","name":"Chad","flag":"\ud83c\uddf9\ud83c\udde9"},{"code":"+56","name":"Chile","flag":"\ud83c\udde8\ud83c\uddf1"},{"code":"+86","name":"China","flag":"\ud83c\udde8\ud83c\uddf3"},{"code":"+61","name":"Christmas Island","flag":"\ud83c\udde8\ud83c\uddfd"},{"code":"+61","name":"Cocos Islands","flag":"\ud83c\udde8\ud83c\udde8"},{"code":"+57","name":"Colombia","flag":"\ud83c\udde8\ud83c\uddf4"},{"code":"+269","name":"Comoros","flag":"\ud83c\uddf0\ud83c\uddf2"},{"code":"+242","name":"Congo","flag":"\ud83c\udde8\ud83c\uddec"},{"code":"+243","name":"Congo (DRC)","flag":"\ud83c\udde8\ud83c\udde9"},{"code":"+682","name":"Cook Islands","flag":"\ud83c\udde8\ud83c\uddf0"},{"code":"+506","name":"Costa Rica","flag":"\ud83c\udde8\ud83c\uddf7"},{"code":"+385","name":"Croatia","flag":"\ud83c\udded\ud83c\uddf7"},{"code":"+53","name":"Cuba","flag":"\ud83c\udde8\ud83c\uddfa"},{"code":"+599","name":"Curacao","flag":"\ud83c\udde8\ud83c\uddfc"},{"code":"+357","name":"Cyprus","flag":"\ud83c\udde8\ud83c\uddfe"},{"code":"+420","name":"Czech Republic","flag":"\ud83c\udde8\ud83c\uddff"},{"code":"+45","name":"Denmark","flag":"\ud83c\udde9\ud83c\uddf0"},{"code":"+253","name":"Djibouti","flag":"\ud83c\udde9\ud83c\uddef"},{"code":"+1-767","name":"Dominica","flag":"\ud83c\udde9\ud83c\uddf2"},{"code":"+1-809","name":"Dominican Republic","flag":"\ud83c\udde9\ud83c\uddf4"},{"code":"+670","name":"East Timor","flag":"\ud83c\uddf9\ud83c\uddf1"},{"code":"+593","name":"Ecuador","flag":"\ud83c\uddea\ud83c\udde8"},{"code":"+20","name":"Egypt","flag":"\ud83c\uddea\ud83c\uddec"},{"code":"+503","name":"El Salvador","flag":"\ud83c\uddf8\ud83c\uddfb"},{"code":"+240","name":"Equatorial Guinea","flag":"\ud83c\uddec\ud83c\uddf6"},{"code":"+291","name":"Eritrea","flag":"\ud83c\uddea\ud83c\uddf7"},{"code":"+372","name":"Estonia","flag":"\ud83c\uddea\ud83c\uddea"},{"code":"+251","name":"Ethiopia","flag":"\ud83c\uddea\ud83c\uddf9"},{"code":"+500","name":"Falkland Islands","flag":"\ud83c\uddeb\ud83c\uddf0"},{"code":"+298","name":"Faroe Islands","flag":"\ud83c\uddeb\ud83c\uddf4"},{"code":"+679","name":"Fiji","flag":"\ud83c\uddeb\ud83c\uddef"},{"code":"+358","name":"Finland","flag":"\ud83c\uddeb\ud83c\uddee"},{"code":"+33","name":"France","flag":"\ud83c\uddeb\ud83c\uddf7"},{"code":"+594","name":"French Guiana","flag":"\ud83c\uddec\ud83c\uddeb"},{"code":"+689","name":"French Polynesia","flag":"\ud83c\uddf5\ud83c\uddeb"},{"code":"+241","name":"Gabon","flag":"\ud83c\uddec\ud83c\udde6"},{"code":"+220","name":"Gambia","flag":"\ud83c\uddec\ud83c\uddf2"},{"code":"+995","name":"Georgia","flag":"\ud83c\uddec\ud83c\uddea"},{"code":"+49","name":"Germany","flag":"\ud83c\udde9\ud83c\uddea"},{"code":"+233","name":"Ghana","flag":"\ud83c\uddec\ud83c\udded"},{"code":"+350","name":"Gibraltar","flag":"\ud83c\uddec\ud83c\uddee"},{"code":"+30","name":"Greece","flag":"\ud83c\uddec\ud83c\uddf7"},{"code":"+299","name":"Greenland","flag":"\ud83c\uddec\ud83c\uddf1"},{"code":"+1-473","name":"Grenada","flag":"\ud83c\uddec\ud83c\udde9"},{"code":"+590","name":"Guadeloupe","flag":"\ud83c\uddec\ud83c\uddf5"},{"code":"+1-671","name":"Guam","flag":"\ud83c\uddec\ud83c\uddfa"},{"code":"+502","name":"Guatemala","flag":"\ud83c\uddec\ud83c\uddf9"},{"code":"+44","name":"Guernsey","flag":"\ud83c\uddec\ud83c\uddec"},{"code":"+224","name":"Guinea","flag":"\ud83c\uddec\ud83c\uddf3"},{"code":"+245","name":"Guinea-Bissau","flag":"\ud83c\uddec\ud83c\uddfc"},{"code":"+592","name":"Guyana","flag":"\ud83c\uddec\ud83c\uddfe"},{"code":"+509","name":"Haiti","flag":"\ud83c\udded\ud83c\uddf9"},{"code":"+504","name":"Honduras","flag":"\ud83c\udded\ud83c\uddf3"},{"code":"+852","name":"Hong Kong","flag":"\ud83c\udded\ud83c\uddf0"},{"code":"+36","name":"Hungary","flag":"\ud83c\udded\ud83c\uddfa"},{"code":"+354","name":"Iceland","flag":"\ud83c\uddee\ud83c\uddf8"},{"code":"+91","name":"India","flag":"\ud83c\uddee\ud83c\uddf3"},{"code":"+62","name":"Indonesia","flag":"\ud83c\uddee\ud83c\udde9"},{"code":"+98","name":"Iran","flag":"\ud83c\uddee\ud83c\uddf7"},{"code":"+964","name":"Iraq","flag":"\ud83c\uddee\ud83c\uddf6"},{"code":"+353","name":"Ireland","flag":"\ud83c\uddee\ud83c\uddea"},{"code":"+44","name":"Isle of Man","flag":"\ud83c\uddee\ud83c\uddf2"},{"code":"+972","name":"Israel","flag":"\ud83c\uddee\ud83c\uddf1"},{"code":"+39","name":"Italy","flag":"\ud83c\uddee\ud83c\uddf9"},{"code":"+225","name":"Ivory Coast","flag":"\ud83c\udde8\ud83c\uddee"},{"code":"+1-876","name":"Jamaica","flag":"\ud83c\uddef\ud83c\uddf2"},{"code":"+81","name":"Japan","flag":"\ud83c\uddef\ud83c\uddf5"},{"code":"+44","name":"Jersey","flag":"\ud83c\uddef\ud83c\uddea"},{"code":"+962","name":"Jordan","flag":"\ud83c\uddef\ud83c\uddf4"},{"code":"+7","name":"Kazakhstan","flag":"\ud83c\uddf0\ud83c\uddff"},{"code":"+254","name":"Kenya","flag":"\ud83c\uddf0\ud83c\uddea"},{"code":"+686","name":"Kiribati","flag":"\ud83c\uddf0\ud83c\uddee"},{"code":"+965","name":"Kuwait","flag":"\ud83c\uddf0\ud83c\uddfc"},{"code":"+996","name":"Kyrgyzstan","flag":"\ud83c\uddf0\ud83c\uddec"},{"code":"+856","name":"Laos","flag":"\ud83c\uddf1\ud83c\udde6"},{"code":"+371","name":"Latvia","flag":"\ud83c\uddf1\ud83c\uddfb"},{"code":"+961","name":"Lebanon","flag":"\ud83c\uddf1\ud83c\udde7"},{"code":"+266","name":"Lesotho","flag":"\ud83c\uddf1\ud83c\uddf8"},{"code":"+231","name":"Liberia","flag":"\ud83c\uddf1\ud83c\uddf7"},{"code":"+218","name":"Libya","flag":"\ud83c\uddf1\ud83c\uddfe"},{"code":"+423","name":"Liechtenstein","flag":"\ud83c\uddf1\ud83c\uddee"},{"code":"+370","name":"Lithuania","flag":"\ud83c\uddf1\ud83c\uddf9"},{"code":"+352","name":"Luxembourg","flag":"\ud83c\uddf1\ud83c\uddfa"},{"code":"+853","name":"Macau","flag":"\ud83c\uddf2\ud83c\uddf4"},{"code":"+389","name":"Macedonia","flag":"\ud83c\uddf2\ud83c\uddf0"},{"code":"+261","name":"Madagascar","flag":"\ud83c\uddf2\ud83c\uddec"},{"code":"+265","name":"Malawi","flag":"\ud83c\uddf2\ud83c\uddfc"},{"code":"+60","name":"Malaysia","flag":"\ud83c\uddf2\ud83c\uddfe"},{"code":"+960","name":"Maldives","flag":"\ud83c\uddf2\ud83c\uddfb"},{"code":"+223","name":"Mali","flag":"\ud83c\uddf2\ud83c\uddf1"},{"code":"+356","name":"Malta","flag":"\ud83c\uddf2\ud83c\uddf9"},{"code":"+692","name":"Marshall Islands","flag":"\ud83c\uddf2\ud83c\udded"},{"code":"+596","name":"Martinique","flag":"\ud83c\uddf2\ud83c\uddf6"},{"code":"+222","name":"Mauritania","flag":"\ud83c\uddf2\ud83c\uddf7"},{"code":"+230","name":"Mauritius","flag":"\ud83c\uddf2\ud83c\uddfa"},{"code":"+262","name":"Mayotte","flag":"\ud83c\uddfe\ud83c\uddf9"},{"code":"+52","name":"Mexico","flag":"\ud83c\uddf2\ud83c\uddfd"},{"code":"+691","name":"Micronesia","flag":"\ud83c\uddeb\ud83c\uddf2"},{"code":"+373","name":"Moldova","flag":"\ud83c\uddf2\ud83c\udde9"},{"code":"+377","name":"Monaco","flag":"\ud83c\uddf2\ud83c\udde8"},{"code":"+976","name":"Mongolia","flag":"\ud83c\uddf2\ud83c\uddf3"},{"code":"+382","name":"Montenegro","flag":"\ud83c\uddf2\ud83c\uddea"},{"code":"+1-664","name":"Montserrat","flag":"\ud83c\uddf2\ud83c\uddf8"},{"code":"+212","name":"Morocco","flag":"\ud83c\uddf2\ud83c\udde6"},{"code":"+258","name":"Mozambique","flag":"\ud83c\uddf2\ud83c\uddff"},{"code":"+95","name":"Myanmar","flag":"\ud83c\uddf2\ud83c\uddf2"},{"code":"+264","name":"Namibia","flag":"\ud83c\uddf3\ud83c\udde6"},{"code":"+674","name":"Nauru","flag":"\ud83c\uddf3\ud83c\uddf7"},{"code":"+977","name":"Nepal","flag":"\ud83c\uddf3\ud83c\uddf5"},{"code":"+31","name":"Netherlands","flag":"\ud83c\uddf3\ud83c\uddf1"},{"code":"+599","name":"Netherlands Antilles","flag":"\ud83c\udde7\ud83c\uddf6"},{"code":"+687","name":"New Caledonia","flag":"\ud83c\uddf3\ud83c\udde8"},{"code":"+64","name":"New Zealand","flag":"\ud83c\uddf3\ud83c\uddff"},{"code":"+505","name":"Nicaragua","flag":"\ud83c\uddf3\ud83c\uddee"},{"code":"+227","name":"Niger","flag":"\ud83c\uddf3\ud83c\uddea"},{"code":"+234","name":"Nigeria","flag":"\ud83c\uddf3\ud83c\uddec"},{"code":"+683","name":"Niue","flag":"\ud83c\uddf3\ud83c\uddfa"},{"code":"+1-670","name":"Northern Mariana Islands","flag":"\ud83c\uddf2\ud83c\uddf5"},{"code":"+47","name":"Norway","flag":"\ud83c\uddf3\ud83c\uddf4"},{"code":"+968","name":"Oman","flag":"\ud83c\uddf4\ud83c\uddf2"},{"code":"+92","name":"Pakistan","flag":"\ud83c\uddf5\ud83c\uddf0"},{"code":"+680","name":"Palau","flag":"\ud83c\uddf5\ud83c\uddfc"},{"code":"+970","name":"Palestine","flag":"\ud83c\uddf5\ud83c\uddf8"},{"code":"+507","name":"Panama","flag":"\ud83c\uddf5\ud83c\udde6"},{"code":"+675","name":"Papua New Guinea","flag":"\ud83c\uddf5\ud83c\uddec"},{"code":"+595","name":"Paraguay","flag":"\ud83c\uddf5\ud83c\uddfe"},{"code":"+51","name":"Peru","flag":"\ud83c\uddf5\ud83c\uddea"},{"code":"+63","name":"Philippines","flag":"\ud83c\uddf5\ud83c\udded"},{"code":"+48","name":"Poland","flag":"\ud83c\uddf5\ud83c\uddf1"},{"code":"+351","name":"Portugal","flag":"\ud83c\uddf5\ud83c\uddf9"},{"code":"+1-787","name":"Puerto Rico","flag":"\ud83c\uddf5\ud83c\uddf7"},{"code":"+974","name":"Qatar","flag":"\ud83c\uddf6\ud83c\udde6"},{"code":"+262","name":"Reunion","flag":"\ud83c\uddf7\ud83c\uddea"},{"code":"+40","name":"Romania","flag":"\ud83c\uddf7\ud83c\uddf4"},{"code":"+7","name":"Russia","flag":"\ud83c\uddf7\ud83c\uddfa"},{"code":"+250","name":"Rwanda","flag":"\ud83c\uddf7\ud83c\uddfc"},{"code":"+590","name":"Saint Barthelemy","flag":"\ud83c\udde7\ud83c\uddf1"},{"code":"+290","name":"Saint Helena","flag":"\ud83c\uddf8\ud83c\udded"},{"code":"+1-869","name":"Saint Kitts and Nevis","flag":"\ud83c\uddf0\ud83c\uddf3"},{"code":"+1-758","name":"Saint Lucia","flag":"\ud83c\uddf1\ud83c\udde8"},{"code":"+590","name":"Saint Martin","flag":"\ud83c\uddf2\ud83c\uddeb"},{"code":"+508","name":"Saint Pierre and Miquelon","flag":"\ud83c\uddf5\ud83c\uddf2"},{"code":"+1-784","name":"Saint Vincent and the Grenadines","flag":"\ud83c\uddfb\ud83c\udde8"},{"code":"+685","name":"Samoa","flag":"\ud83c\uddfc\ud83c\uddf8"},{"code":"+378","name":"San Marino","flag":"\ud83c\uddf8\ud83c\uddf2"},{"code":"+239","name":"Sao Tome and Principe","flag":"\ud83c\uddf8\ud83c\uddf9"},{"code":"+966","name":"Saudi Arabia","flag":"\ud83c\uddf8\ud83c\udde6"},{"code":"+221","name":"Senegal","flag":"\ud83c\uddf8\ud83c\uddf3"},{"code":"+381","name":"Serbia","flag":"\ud83c\uddf7\ud83c\uddf8"},{"code":"+248","name":"Seychelles","flag":"\ud83c\uddf8\ud83c\udde8"},{"code":"+232","name":"Sierra Leone","flag":"\ud83c\uddf8\ud83c\uddf1"},{"code":"+65","name":"Singapore","flag":"\ud83c\uddf8\ud83c\uddec"},{"code":"+1-721","name":"Sint Maarten","flag":"\ud83c\uddf8\ud83c\uddfd"},{"code":"+421","name":"Slovakia","flag":"\ud83c\uddf8\ud83c\uddf0"},{"code":"+386","name":"Slovenia","flag":"\ud83c\uddf8\ud83c\uddee"},{"code":"+677","name":"Solomon Islands","flag":"\ud83c\uddf8\ud83c\udde7"},{"code":"+252","name":"Somalia","flag":"\ud83c\uddf8\ud83c\uddf4"},{"code":"+27","name":"South Africa","flag":"\ud83c\uddff\ud83c\udde6"},{"code":"+82","name":"South Korea","flag":"\ud83c\uddf0\ud83c\uddf7"},{"code":"+211","name":"South Sudan","flag":"\ud83c\uddf8\ud83c\uddf8"},{"code":"+34","name":"Spain","flag":"\ud83c\uddea\ud83c\uddf8"},{"code":"+94","name":"Sri Lanka","flag":"\ud83c\uddf1\ud83c\uddf0"},{"code":"+249","name":"Sudan","flag":"\ud83c\uddf8\ud83c\udde9"},{"code":"+597","name":"Suriname","flag":"\ud83c\uddf8\ud83c\uddf7"},{"code":"+268","name":"Swaziland","flag":"\ud83c\uddf8\ud83c\uddff"},{"code":"+46","name":"Sweden","flag":"\ud83c\uddf8\ud83c\uddea"},{"code":"+41","name":"Switzerland","flag":"\ud83c\udde8\ud83c\udded"},{"code":"+963","name":"Syria","flag":"\ud83c\uddf8\ud83c\uddfe"},{"code":"+886","name":"Taiwan","flag":"\ud83c\uddf9\ud83c\uddfc"},{"code":"+992","name":"Tajikistan","flag":"\ud83c\uddf9\ud83c\uddef"},{"code":"+255","name":"Tanzania","flag":"\ud83c\uddf9\ud83c\uddff"},{"code":"+66","name":"Thailand","flag":"\ud83c\uddf9\ud83c\udded"},{"code":"+228","name":"Togo","flag":"\ud83c\uddf9\ud83c\uddec"},{"code":"+690","name":"Tokelau","flag":"\ud83c\uddf9\ud83c\uddf0"},{"code":"+676","name":"Tonga","flag":"\ud83c\uddf9\ud83c\uddf4"},{"code":"+1-868","name":"Trinidad and Tobago","flag":"\ud83c\uddf9\ud83c\uddf9"},{"code":"+216","name":"Tunisia","flag":"\ud83c\uddf9\ud83c\uddf3"},{"code":"+90","name":"Turkey","flag":"\ud83c\uddf9\ud83c\uddf7"},{"code":"+993","name":"Turkmenistan","flag":"\ud83c\uddf9\ud83c\uddf2"},{"code":"+1-649","name":"Turks and Caicos Islands","flag":"\ud83c\uddf9\ud83c\udde8"},{"code":"+688","name":"Tuvalu","flag":"\ud83c\uddf9\ud83c\uddfb"},{"code":"+256","name":"Uganda","flag":"\ud83c\uddfa\ud83c\uddec"},{"code":"+380","name":"Ukraine","flag":"\ud83c\uddfa\ud83c\udde6"},{"code":"+971","name":"United Arab Emirates","flag":"\ud83c\udde6\ud83c\uddea"},{"code":"+44","name":"United Kingdom","flag":"\ud83c\uddec\ud83c\udde7"},{"code":"+1","name":"United States","flag":"\ud83c\uddfa\ud83c\uddf8"},{"code":"+598","name":"Uruguay","flag":"\ud83c\uddfa\ud83c\uddfe"},{"code":"+998","name":"Uzbekistan","flag":"\ud83c\uddfa\ud83c\uddff"},{"code":"+678","name":"Vanuatu","flag":"\ud83c\uddfb\ud83c\uddfa"},{"code":"+379","name":"Vatican","flag":"\ud83c\uddfb\ud83c\udde6"},{"code":"+58","name":"Venezuela","flag":"\ud83c\uddfb\ud83c\uddea"},{"code":"+84","name":"Vietnam","flag":"\ud83c\uddfb\ud83c\uddf3"},{"code":"+1-340","name":"Virgin Islands","flag":"\ud83c\uddfb\ud83c\uddee"},{"code":"+681","name":"Wallis and Futuna","flag":"\ud83c\uddfc\ud83c\uddeb"},{"code":"+967","name":"Yemen","flag":"\ud83c\uddfe\ud83c\uddea"},{"code":"+260","name":"Zambia","flag":"\ud83c\uddff\ud83c\uddf2"},{"code":"+263","name":"Zimbabwe","flag":"\ud83c\uddff\ud83c\uddfc"}];
    const country = countries.find(c => c.code === booking.country_code);

    const statusColors = {
        'Pending': '#ff9729',
        'Confirmed': '#088529',
        'Completed': '#854208',
        'Cancelled': '#dc2626'
    };
    const statusColor = statusColors[booking.status] || '#854208';

    const content = `
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <!-- Header Section -->
            <div style="
                display: flex; 
                align-items: center; 
                justify-content: space-between; 
                padding: 16px; 
                background: linear-gradient(135deg, #ff9729 0%, #088529 100%); 
                border-radius: 12px; 
                color: white;">
                <div>
                    <div style="font-size: 20px; font-weight: 700; font-family: 'Raleway', sans-serif;">Booking #${booking.id}</div>
                    <div style="font-size: 13px; opacity: 0.9;">Created: ${new Date(booking.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</div>
                </div>
                <span style="
                    display: inline-flex; 
                    align-items: center; 
                    padding: 6px 14px; 
                    background: rgba(255,255,255,0.2); 
                    border-radius: 9999px; 
                    font-size: 12px; 
                    font-weight: 600;">
                    ${booking.status}
                </span>
            </div>
            
            <!-- Guest Info -->
            <div style="
                padding: 20px; 
                background: #f8f4f0; 
                border-radius: 12px; 
                border: 1px solid rgba(133, 66, 8, 0.1);">
                <div style="
                    font-size: 13px; 
                    font-weight: 700; 
                    color: #854208; 
                    text-transform: uppercase; 
                    letter-spacing: 0.05em; 
                    margin-bottom: 16px;">
                    Guest Information
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div>
                        <div style="
                            font-size: 12px; 
                            color: #854208; 
                            opacity: 0.7; 
                            margin-bottom: 4px;">
                            Guest Name
                        </div>
                        <div style="
                            font-size: 15px; 
                            color: #111111; 
                            font-weight: 600;">
                            ${booking.name ?? booking.email}
                        </div>
                    </div>
                    <div>
                        <div style="
                            font-size: 12px; 
                            color: #854208; 
                            opacity: 0.7; 
                            margin-bottom: 4px;">
                            Email
                        </div>
                        <div style="
                            font-size: 15px; 
                            color: #111111; 
                            font-weight: 600;">
                            ${booking.email}
                        </div>
                    </div>
                    <div>
                        <div style="
                            font-size: 12px; 
                            color: #854208; 
                            opacity: 0.7; 
                            margin-bottom: 4px;">
                            Phone
                        </div>
                        <div style="
                            font-size: 15px; 
                            color: #111111; 
                            font-weight: 600;">
                            ${country ? country.flag : ''} ${booking.country_code ?? ''} ${booking.phone_number}
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tour Info -->
            <div style="
                padding: 20px; 
                background: #f8f4f0; 
                border-radius: 12px; 
                border: 1px solid rgba(133, 66, 8, 0.1);">
                <div style="
                    font-size: 13px; 
                    font-weight: 700; 
                    color: #854208; 
                    text-transform: uppercase; 
                    letter-spacing: 0.05em; 
                    margin-bottom: 16px;">
                    Tour Details
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div>
                        <div style="
                            font-size: 12px; 
                            color: #854208; 
                            opacity: 0.7; 
                            margin-bottom: 4px;">
                            Tour
                        </div>
                        <div style="
                            font-size: 15px; 
                            color: #111111; 
                            font-weight: 600;">
                            ${booking.tour_name}
                        </div>
                    </div>
                    <div>
                        <div style="
                            font-size: 12px; 
                            color: #854208; 
                            opacity: 0.7; 
                            margin-bottom: 4px;">
                            Travel Date
                        </div>
                        <div style="
                            font-size: 15px; 
                            color: #111111; 
                            font-weight: 600;">
                            ${booking.travel_date ? new Date(booking.travel_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'Not set'}
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pricing & Guests -->
            <div style="
                padding: 20px; 
                background: #f8f4f0; 
                border-radius: 12px; 
                border: 1px solid rgba(133, 66, 8, 0.1);">
                <div style="
                    font-size: 13px; 
                    font-weight: 700; 
                    color: #854208; 
                    text-transform: uppercase; 
                    letter-spacing: 0.05em; 
                    margin-bottom: 16px;">
                    Pricing & Guests
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div style="
                        padding: 12px; 
                        background: white; 
                        border-radius: 8px; 
                        border: 1px solid rgba(133, 66, 8, 0.1);">
                        <div style="
                            font-size: 12px; 
                            color: #854208; 
                            opacity: 0.7; 
                            margin-bottom: 4px;">
                            Adults
                        </div>
                        <div style="
                            font-size: 24px; 
                            color: #088529; 
                            font-weight: 700;">
                            ${booking.adults ?? (booking.guests ?? 0)}
                        </div>
                    </div>
                    <div style="
                        padding: 12px; 
                        background: white; 
                        border-radius: 8px; 
                        border: 1px solid rgba(133, 66, 8, 0.1);">
                        <div style="
                            font-size: 12px; 
                            color: #854208; 
                            opacity: 0.7; 
                            margin-bottom: 4px;">
                            Children
                        </div>
                        <div style="
                            font-size: 24px; 
                            color: #088529; 
                            font-weight: 700;">
                            ${booking.children ?? 0}
                        </div>
                    </div>
                </div>
                <div style="
                        display: flex; 
                        align-items: center; 
                        justify-content: space-between; 
                        padding: 16px; 
                        background: white; 
                        border-radius: 8px; 
                        border: 1px solid rgba(133, 66, 8, 0.1);">
                    <div style="
                        font-size: 14px; 
                        color: #854208; 
                        font-weight: 600;">
                        Total Amount
                    </div>
                    <div>
                        <div style="
                            font-size: 28px; 
                            color: #088529; 
                            font-weight: 700; 
                            font-family: 'Raleway', sans-serif;">
                            ${booking.currency ?? '$'}${(booking.amount ?? booking.total_price).toLocaleString()}
                        </div>
                        <div style="
                            font-size: 14px; 
                            color: var(--ink-soft); 
                            margin-top: 4px;" 
                            id="viewBookingConverted">
                            ~${formatCurrency(convertCurrency((booking.amount ?? booking.total_price), booking.currency ?? 'USD', document.getElementById('bookingsCurrencySwitcher').value), document.getElementById('bookingsCurrencySwitcher').value)}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('viewBookingContent').innerHTML = content;
    openModal('viewBookingModalBackdrop');
}
</script>


<script>
// Payment link dropdown
function togglePaydrop(e, btn) {
    e.stopPropagation();
    const menu = btn.nextElementSibling;
    const isOpen = menu.classList.contains('open');
    document.querySelectorAll('.paydrop-menu.open').forEach(m => m.classList.remove('open'));
    if (!isOpen) menu.classList.add('open');
}
document.addEventListener('click', e => {
    if (!e.target.closest('.paydrop')) {
        document.querySelectorAll('.paydrop-menu.open').forEach(m => m.classList.remove('open'));
    }
});
document.querySelectorAll('[data-copy-link]').forEach(btn => {
    btn.addEventListener('click', async function () {
        const bookingId = this.dataset.copyLink;
        try {
            const res = await fetch('/live/bookings/' + bookingId + '/payment-link/copy', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await res.json();
            if (data.url) {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(data.url);
                } else {
                    const ta = document.createElement('textarea');
                    ta.value = data.url;
                    ta.style.position = 'fixed';
                    ta.style.left = '-9999px';
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    document.body.removeChild(ta);
                }
                document.querySelectorAll('.paydrop-menu.open').forEach(m => m.classList.remove('open'));
                toast('Payment link copied', 'success');
            } else {
                toast('Could not generate payment link', 'error');
            }
        } catch (err) {
            toast('Could not generate payment link', 'error');
        }
    });
});
</script>
            </div>
        </div>
    </div>
    <div id="toastHost"></div>
    <script>
        // Auto-logout timer (5 minutes in milliseconds)
        const AUTO_LOGOUT_TIME = 5 * 60 * 1000;
        let autoLogoutTimer;

        // Reset the auto-logout timer on user activity
        function resetAutoLogoutTimer() {
            clearTimeout(autoLogoutTimer);
            autoLogoutTimer = setTimeout(() => {
                // Clear the session on the server side by redirecting to login
                window.location.href = "https://tanzaniadailytoursandsafari.com/live/login";
            }, AUTO_LOGOUT_TIME);
        }

        // Add event listeners for user activity
        ['click', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
            document.addEventListener(event, resetAutoLogoutTimer, true);
        });

        // Initialize the timer when the page loads
        resetAutoLogoutTimer();

        function toggleSidebar(){
            if(window.innerWidth <= 900){
                document.getElementById('sidebar').classList.toggle('mobile-open');
                document.getElementById('mobileOverlay').classList.toggle('show');
            } else {
                document.getElementById('sidebar').classList.toggle('collapsed');
            }
        }
        function closeMobileSidebar(){
            document.getElementById('sidebar').classList.remove('mobile-open');
            document.getElementById('mobileOverlay').classList.remove('show');
        }
        function toggleSbDrop(el){
            const drop = el.closest('.sb-drop');
            const wasOpen = drop.classList.contains('open');
            document.querySelectorAll('.sb-drop').forEach(d => d.classList.remove('open'));
            if(!wasOpen) drop.classList.add('open');
        }

        function toast(msg, type='default'){
            const host = document.getElementById('toastHost');
            const el = document.createElement('div');
            el.className = 'toast ' + (type==='success'?'success':type==='error'?'error':'');
            el.innerHTML = (type==='success' ? '<svg viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><path d=\"M20 6 9 17l-5-5\"></path></svg>' : '') + '<span>'+msg+'</span>';
            host.appendChild(el);
            setTimeout(()=>{ el.style.opacity='0'; el.style.transform='translateX(20px)'; el.style.transition='all .25s'; setTimeout(()=>el.remove(),250); }, 3000);
        }

        function openModal(id){ document.getElementById(id).classList.add('show'); }
        function closeModal(id){ document.getElementById(id).classList.remove('show'); }
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="w3Vlfvw2Bh8VfqolGpMryy5mbgT3npZg5cr409mV">
    <title>Payments · Tanzania Daily Tours & Safari</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        :root{
            --sand-50:#FBF7EF;
            --sand-100:#F4ECDC;
            --sand-200:#E9DCC0;
            --coffee-900:#2A1B10;
            --coffee-800:#3B2718;
            --coffee-700:#4D3422;
            --coffee-500:#7A5C42;
            --coffee-300:#A98968;
            --terracotta-600:#C2592B;
            --terracotta-500:#D06B3A;
            --terracotta-100:#F6E1D3;
            --acacia-600:#5E6E3F;
            --acacia-500:#7A8450;
            --acacia-100:#E2E7D4;
            --gold-500:#D4A24C;
            --gold-100:#F7E9CB;
            --ink:#241408;
            --ink-soft:#6B5A48;
            --line:#E4D7C2;
            --white:#FFFFFF;
            --danger:#B33A3A;
            --danger-100:#F6DCDA;
            --success:#3F6B3F;
            --radius-sm:8px;
            --radius-md:14px;
            --radius-lg:20px;
            --shadow-sm:0 1px 2px rgba(42,27,16,.08);
            --shadow-md:0 8px 24px rgba(42,27,16,.10);
            --shadow-lg:0 20px 48px rgba(42,27,16,.18);
            --sidebar-w:264px;
            --sidebar-w-collapsed:76px;
            --topbar-h:72px;
        }

        *{box-sizing:border-box;}
        html,body{height:100%;}
        body{
            margin:0;
            font-family:'Raleway',sans-serif;
            background:var(--sand-50);
            color:var(--ink);
            -webkit-font-smoothing:antialiased;
            overflow-x:hidden;
        }
        ::selection{background:var(--terracotta-100);color:var(--coffee-900);}
        h1,h2,h3,h4{font-family:'Raleway',sans-serif;margin:0;color:var(--coffee-900);letter-spacing:-0.01em;}
        p{margin:0;}
        a{color:inherit;text-decoration:none;}
        button{font-family:inherit;cursor:pointer;}
        input,select,textarea{font-family:inherit;}
        .mono{font-family:'Raleway',sans-serif;}
        ::-webkit-scrollbar{width:9px;height:9px;}
        ::-webkit-scrollbar-track{background:transparent;}
        ::-webkit-scrollbar-thumb{background:var(--coffee-300);border-radius:10px;}
        ::-webkit-scrollbar-thumb:hover{background:var(--coffee-500);}


        /* Sidebar */
        .sidebar{
            position:fixed;top:0;left:0;bottom:0;width:var(--sidebar-w);z-index:200;
            background:var(--coffee-900);
            background-image:radial-gradient(circle at 0% 0%, rgba(212,162,76,.10), transparent 55%);
            display:flex;flex-direction:column;
            transition:width .25s ease, transform .25s ease;
            border-right:1px solid rgba(255,255,255,.06);
        }
        .sidebar.collapsed{width:var(--sidebar-w-collapsed);}
        .sb-brand{
            display:flex;align-items:center;gap:12px;padding:22px 20px;
            border-bottom:1px solid rgba(255,255,255,.08);min-height:var(--topbar-h);
        }
        .sb-mark{
            width:38px;height:38px;border-radius:10px;flex:none;
            background:linear-gradient(155deg,var(--terracotta-600),var(--gold-500));
            display:flex;align-items:center;justify-content:center;box-shadow:var(--shadow-sm);
        }
        .sb-mark svg{width:21px;height:21px;}
        .sb-brand-text{overflow:hidden;white-space:nowrap;}
        .sb-brand-text strong{display:block;color:#fff;font-family:'Raleway',sans-serif;font-size:15.5px;line-height:1.2;}
        .sb-brand-text span{display:block;color:var(--gold-500);font-size:11px;letter-spacing:.06em;text-transform:uppercase;font-weight:600;}
        .sidebar.collapsed .sb-brand-text{display:none;}
        .sb-nav{flex:1;overflow-y:auto;padding:16px 12px;}
        .sb-section-label{
            color:rgba(255,255,255,.32);font-size:10.5px;font-weight:700;letter-spacing:.09em;
            text-transform:uppercase;padding:14px 12px 8px;
        }
        .sidebar.collapsed .sb-section-label{display:none;}
        .sb-item{
            display:flex;align-items:center;gap:13px;padding:11px 12px;border-radius:10px;
            color:rgba(255,255,255,.62);font-size:14px;font-weight:500;margin-bottom:2px;
            position:relative;transition:background .15s,color .15s;white-space:nowrap;
        }
        .sb-item:hover{background:rgba(255,255,255,.06);color:#fff;}
        .sb-item.active{background:rgba(212,162,76,.16);color:var(--gold-500);}
        .sb-item.active::before{
            content:"";position:absolute;left:-12px;top:8px;bottom:8px;width:3px;border-radius:3px;
            background:var(--gold-500);
        }
        .sb-item svg{width:19px;height:19px;flex:none;}
        .sb-item .badge{
            margin-left:auto;background:var(--terracotta-600);color:#fff;font-size:11px;font-weight:700;
            padding:1px 7px;border-radius:20px;
        }
        .sidebar.collapsed .sb-item span:not(.badge){display:none;}
        .sidebar.collapsed .sb-item .badge{display:none;}
        .sidebar.collapsed .sb-item{justify-content:center;}
        .sb-drop{position:relative;}
        .sb-drop-toggle{width:100%;cursor:pointer;background:none;border:none;font-family:inherit;}
        .sb-drop-toggle .chev{margin-left:auto;opacity:.55;transition:transform .25s ease;width:15px;height:15px;flex:none;}
        .sb-drop.open .sb-drop-toggle .chev{transform:rotate(180deg);}
        .sb-drop-menu{display:none;margin:2px 0 4px;padding-left:12px;}
        .sb-drop.open .sb-drop-menu{display:block;}
        .sidebar.collapsed .sb-drop-menu{display:none;}
        .sb-drop-sub{display:flex;align-items:center;gap:9px;padding:9px 12px;border-radius:8px;margin-bottom:1px;color:rgba(255,255,255,.55);font-size:13px;font-weight:500;text-decoration:none;transition:background .15s ease,color .15s ease;}
        .sb-drop-sub:hover{color:#fff;background:rgba(255,255,255,.06);}
        .sb-drop-sub.active{color:var(--gold-500);background:rgba(212,162,76,.12);}
        .sb-drop-sub svg{width:14px;height:14px;flex:none;}
        .sb-footer{padding:14px 20px 20px;border-top:1px solid rgba(255,255,255,.08);}
        .sb-user{display:flex;align-items:center;gap:11px;}
        .sb-avatar{
            width:36px;height:36px;border-radius:50%;flex:none;
            background:linear-gradient(155deg,var(--acacia-500),var(--acacia-600));
            display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:13px;
        }
        .sb-user-text{overflow:hidden;white-space:nowrap;}
        .sb-user-text strong{display:block;color:#fff;font-size:13.5px;}
        .sb-user-text span{display:block;color:rgba(255,255,255,.5);font-size:11.5px;}
        .sidebar.collapsed .sb-user-text{display:none;}


        /* Main */
        .main{margin-left:var(--sidebar-w);transition:margin-left .25s ease;min-height:100vh;display:flex;flex-direction:column;}
        .sidebar.collapsed ~ .main{margin-left:var(--sidebar-w-collapsed);}
        .topbar{
            position:sticky;top:0;z-index:100;height:var(--topbar-h);
            background:rgba(251,247,239,.86);backdrop-filter:blur(10px);
            border-bottom:1px solid var(--line);
            display:flex;align-items:center;gap:16px;padding:0 28px;
        }
        .tb-toggle{
            width:38px;height:38px;border-radius:10px;border:1.5px solid var(--line);background:var(--white);
            display:flex;align-items:center;justify-content:center;flex:none;
        }
        .tb-toggle svg{width:18px;height:18px;color:var(--coffee-700);}
        .tb-toggle:hover{background:var(--sand-100);}
        .tb-search{
            flex:1;max-width:420px;display:flex;align-items:center;gap:10px;
            background:var(--white);border:1.5px solid var(--line);border-radius:11px;padding:9px 14px;
        }
        .tb-search svg{width:17px;height:17px;color:var(--ink-soft);flex:none;}
        .tb-search input{border:none;outline:none;background:transparent;font-size:14px;width:100%;color:var(--ink);}
        .tb-right{margin-left:auto;display:flex;align-items:center;gap:10px;}
        .tb-iconbtn{
            width:40px;height:40px;border-radius:11px;border:1.5px solid var(--line);background:var(--white);
            display:flex;align-items:center;justify-content:center;position:relative;color:var(--coffee-700);
        }
        .tb-iconbtn:hover{background:var(--sand-100);}
        .tb-iconbtn svg{width:18px;height:18px;}
        .tb-dot{
            position:absolute;top:7px;right:7px;width:8px;height:8px;border-radius:50%;
            background:var(--terracotta-600);border:2px solid var(--sand-50);
        }
        .tb-live{
            display:flex;align-items:center;gap:7px;background:var(--acacia-100);color:var(--acacia-600);
            padding:7px 13px;border-radius:20px;font-size:12.5px;font-weight:700;
        }
        .tb-live::before{
            content:"";width:7px;height:7px;border-radius:50%;background:var(--acacia-600);box-shadow:0 0 0 0 rgba(94,110,63,.5);animation:pulse 2s infinite;
        }
        @keyframes pulse{
            0%{box-shadow:0 0 0 0 rgba(94,110,63,.45);}
            70%{box-shadow:0 0 0 7px rgba(94,110,63,0);}
            100%{box-shadow:0 0 0 0 rgba(94,110,63,0);}
        }
        .view-wrap{padding:28px;flex:1;}
        .view{display:none;animation:fadeUp .35s ease;}
        .view.active{display:block;}
        @keyframes fadeUp{from{opacity:0;transform:translateY(8px);}to{opacity:1;transform:translateY(0);}}
        .view-head{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:24px;flex-wrap:wrap;}
        .view-head h2{font-size:27px;}
        .view-head .sub{color:var(--ink-soft);font-size:14px;margin-top:5px;}
        .view-actions{display:flex;gap:10px;flex-wrap:wrap;}


        /* Stats */
        .stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:24px;}
        .stat-card{
            background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);
            padding:20px 20px 18px;box-shadow:var(--shadow-sm);position:relative;overflow:hidden;
        }
        .stat-card::after{content:"";position:absolute;right:-20px;top:-20px;width:90px;height:90px;border-radius:50%;background:var(--stat-tint,var(--terracotta-100));opacity:.5;}
        .stat-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
        .stat-icon{width:40px;height:40px;border-radius:11px;display:flex;align-items:center;justify-content:center;background:var(--stat-tint,var(--terracotta-100));color:var(--stat-fg,var(--terracotta-600));position:relative;}
        .stat-icon svg{width:20px;height:20px;}
        .stat-trend{font-size:12px;font-weight:700;padding:3px 8px;border-radius:20px;}
        .stat-trend.up{color:var(--success);background:var(--acacia-100);}
        .stat-trend.down{color:var(--danger);background:var(--danger-100);}
        .stat-value{font-family:'Raleway',sans-serif;font-size:30px;color:var(--coffee-900);position:relative;}
        .stat-label{font-size:13px;color:var(--ink-soft);margin-top:4px;position:relative;}

        .panel-grid{display:grid;grid-template-columns:1.55fr 1fr;gap:18px;margin-bottom:24px;}
        .panel{background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);overflow:hidden;}
        .panel-head{display:flex;align-items:center;justify-content:space-between;padding:18px 20px 14px;border-bottom:1px solid var(--line);}
        .panel-head h3{font-size:16.5px;}
        .panel-head .link{font-size:12.5px;font-weight:700;color:var(--terracotta-600);cursor:pointer;}
        .panel-body{padding:18px 20px 20px;}

        /* Bar Chart */
        .bars{display:flex;align-items:flex-end;gap:10px;height:170px;padding-top:10px;}
        .bar-col{flex:1;display:flex;flex-direction:column;align-items:center;gap:8px;height:100%;justify-content:flex-end;}
        .bar{
            width:100%;max-width:30px;border-radius:6px 6px 2px 2px;
            background:linear-gradient(180deg,var(--terracotta-500),var(--terracotta-600));
            transition:height .6s cubic-bezier(.2,.8,.2,1);position:relative;
        }
        .bar-col:nth-child(even) .bar{background:linear-gradient(180deg,var(--gold-500),#bb8636);}
        .bar-label{font-size:11px;color:var(--ink-soft);font-weight:600;}

        .donut-wrap{display:flex;align-items:center;gap:18px;}
        .legend{display:flex;flex-direction:column;gap:10px;flex:1;}
        .legend-item{display:flex;align-items:center;gap:9px;font-size:13px;}
        .legend-dot{width:10px;height:10px;border-radius:3px;flex:none;}
        .legend-item b{margin-left:auto;color:var(--coffee-900);}

        .activity-list{display:flex;flex-direction:column;gap:3px;}
        .activity-row{display:flex;gap:12px;padding:11px 0;border-bottom:1px dashed var(--line);}
        .activity-row:last-child{border-bottom:none;}
        .activity-ico{width:34px;height:34px;border-radius:9px;flex:none;display:flex;align-items:center;justify-content:center;background:var(--sand-100);color:var(--coffee-700);}
        .activity-ico svg{width:16px;height:16px;}
        .activity-text{font-size:13.5px;line-height:1.4;}
        .activity-text b{color:var(--coffee-900);}
        .activity-time{font-size:11.5px;color:var(--ink-soft);margin-top:2px;}


        /* Tables */
        .table-card{background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);overflow:hidden;}
        .table-toolbar{display:flex;align-items:center;gap:10px;padding:16px 18px;border-bottom:1px solid var(--line);flex-wrap:wrap;}
        .chip-filters{display:flex;gap:8px;flex-wrap:wrap;}
        .chip{padding:7px 14px;border-radius:20px;font-size:12.5px;font-weight:600;background:var(--sand-100);color:var(--coffee-700);border:1px solid transparent;cursor:pointer;display:inline-flex;align-items:center;text-decoration:none;}
        .chip.active{background:var(--coffee-900);color:#fff;}
        .table-search{display:flex;align-items:center;gap:8px;background:var(--sand-50);border:1.5px solid var(--line);border-radius:10px;padding:8px 12px;margin-left:auto;min-width:200px;}
        .table-search svg{width:15px;height:15px;color:var(--ink-soft);}
        .table-search input{border:none;background:transparent;outline:none;font-size:13.5px;width:100%;}
        .table-scroll{overflow-x:auto;}
        .table-pager{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 18px;border-top:1px solid var(--line);flex-wrap:wrap;}
        .pager-pages{display:flex;gap:6px;align-items:center;flex-wrap:wrap;}
        .pager-pages a,.pager-pages span.page{min-width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;padding:0 8px;border:1px solid var(--line);border-radius:8px;font-size:12.5px;font-weight:600;color:var(--coffee-700);text-decoration:none;background:var(--white);}
        .pager-pages a:hover{border-color:var(--gold-500);color:var(--terracotta-600);}
        .pager-pages span.active{background:var(--coffee-900);color:#fff;border-color:var(--coffee-900);}
        table{width:100%;border-collapse:collapse;min-width:680px;}
        thead th{
            text-align:left;font-size:11.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--ink-soft);
            padding:12px 18px;border-bottom:1px solid var(--line);background:var(--sand-50);font-weight:700;white-space:nowrap;
        }
        tbody td{padding:14px 18px;border-bottom:1px solid var(--line);font-size:13.5px;color:var(--coffee-900);}
        tbody tr:last-child td{border-bottom:none;}
        .table-pagination{padding:16px 18px;border-top:1px solid var(--line);display:flex;align-items:center;justify-content:center;gap:8px;}
        .table-pagination a,.table-pagination span{padding:8px 14px;border-radius:8px;font-size:12.5px;font-weight:600;border:1px solid var(--line);background:var(--white);color:var(--coffee-700);text-decoration:none;transition:all .2s;}
        .table-pagination a:hover{background:var(--sand-100);border-color:var(--coffee-300);}
        .table-pagination .active{background:var(--coffee-900);color:#fff;border-color:var(--coffee-900);}
        .table-pagination .disabled{opacity:.5;cursor:not-allowed;}
        tbody tr{transition:background .12s;}
        tbody tr:hover{background:var(--sand-50);}
        .cell-main{display:flex;align-items:center;gap:11px;}
        .thumb{width:42px;height:42px;border-radius:9px;object-fit:cover;flex:none;background:var(--sand-200);}
        .cell-title{font-weight:600;color:var(--coffee-900);}
        .cell-sub{font-size:12px;color:var(--ink-soft);margin-top:1px;}
        .tag{display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:20px;font-size:11.5px;font-weight:700;white-space:nowrap;}
        .tag-green{background:var(--acacia-100);color:var(--acacia-600);}
        .tag-gold{background:var(--gold-100);color:#8a6418;}
        .tag-red{background:var(--danger-100);color:var(--danger);}
        .tag-grey{background:var(--sand-200);color:var(--ink-soft);}
        .tag-terracotta{background:var(--terracotta-100);color:var(--terracotta-600);}
        .row-actions{display:flex;gap:6px;justify-content:flex-end;}
        .row-actions button{width:32px;height:32px;border-radius:8px;border:1px solid var(--line);background:var(--white);display:flex;align-items:center;justify-content:center;color:var(--coffee-700);}
        .row-actions button:hover{background:var(--sand-100);}
        .row-actions button svg{width:14.5px;height:14.5px;}
        .row-actions .danger:hover{background:var(--danger-100);color:var(--danger);border-color:var(--danger-100);}
        .row-actions .warn:hover{background:var(--gold-100);color:#8a6418;border-color:var(--gold-100);}
        .pwd-wrap{position:relative;}
        .pwd-wrap input{padding-right:42px;}
        .pwd-toggle{position:absolute;right:4px;top:50%;transform:translateY(-50%);width:32px;height:32px;border:none;background:transparent;cursor:pointer;font-size:14px;opacity:.7;border-radius:8px;}
        .pwd-toggle:hover{opacity:1;background:var(--sand-200);}
        .input-icon-wrap{position:relative;}
        .input-icon-wrap .input-icon{position:absolute;left:14px;top:0;bottom:0;margin:auto 0;width:16px;height:16px;color:var(--coffee-300);pointer-events:none;}
        .field .input-icon-wrap input,.field .input-icon-wrap textarea{padding-left:42px;}
        .settings-section{display:flex;align-items:center;gap:9px;margin:24px 0 4px;padding-top:8px;}
        .settings-section:first-of-type{margin-top:0;padding-top:0;}
        .settings-section::before{content:'';width:22px;height:3px;border-radius:2px;background:var(--gold-500);flex:none;}
        .settings-section h4{margin:0;font-size:12.5px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--coffee-700);}
        .empty-state{padding:60px 20px;text-align:center;color:var(--ink-soft);}
        .empty-state svg{width:46px;height:46px;color:var(--coffee-300);margin-bottom:12px;}
        .empty-state h4{margin-bottom:5px;color:var(--coffee-800);}
        .empty-state p{font-size:13.5px;}


        /* Buttons */
        .btn{
            display:inline-flex;align-items:center;justify-content:center;gap:8px;
            padding:12px 20px;border-radius:var(--radius-sm);border:none;
            font-weight:600;font-size:14.5px;transition:transform .12s, box-shadow .12s, background .15s;
        }
        .btn:active{transform:translateY(1px);}
        .btn-primary{background:var(--terracotta-600);color:#fff;box-shadow:0 6px 16px rgba(194,89,43,.32);}
        .btn-primary:hover{background:var(--terracotta-500);}
        .btn-ghost{background:transparent;color:var(--coffee-700);border:1.5px solid var(--line);}
        .btn-ghost:hover{background:var(--sand-100);}
        .btn-soft{background:var(--sand-100);color:var(--coffee-800);}
        .btn-soft:hover{background:var(--sand-200);}
        .btn-danger{background:var(--danger-100);color:var(--danger);}
        .btn-danger:hover{background:#efc6c2;}
        .btn-sm{padding:8px 13px;font-size:13px;}


        /* Gallery */
        .gallery-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:16px;}
        .gallery-card{position:relative;border-radius:var(--radius-md);overflow:hidden;aspect-ratio:1/1;box-shadow:var(--shadow-sm);border:1px solid var(--line);background:var(--sand-200);}
        .gallery-card img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .35s;}
        .gallery-card:hover img{transform:scale(1.06);}
        .gallery-overlay{
            position:absolute;inset:0;background:linear-gradient(180deg,rgba(36,20,8,0) 45%,rgba(36,20,8,.82));
            display:flex;flex-direction:column;justify-content:flex-end;padding:12px;opacity:0;transition:opacity .2s;
        }
        .gallery-card:hover .gallery-overlay{opacity:1;}
        .gallery-cap{color:#fff;font-size:12.5px;font-weight:600;margin-bottom:8px;line-height:1.3;}
        .gallery-actions{display:flex;gap:6px;}
        .gallery-actions button{flex:1;padding:6px;border-radius:7px;border:none;background:rgba(255,255,255,.18);color:#fff;backdrop-filter:blur(4px);font-size:11.5px;font-weight:600;display:flex;align-items:center;justify-content:center;gap:5px;}
        .gallery-actions button:hover{background:rgba(255,255,255,.3);}
        .gallery-badge{position:absolute;top:10px;left:10px;background:rgba(36,20,8,.65);color:#fff;font-size:10.5px;font-weight:700;padding:4px 9px;border-radius:20px;text-transform:uppercase;letter-spacing:.04em;backdrop-filter:blur(4px);}
        .add-tile{
            border:2px dashed var(--coffee-300);border-radius:var(--radius-md);aspect-ratio:1/1;
            display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;color:var(--coffee-500);
            background:var(--sand-100);font-size:12.5px;font-weight:600;cursor:pointer;
        }
        .add-tile:hover{background:var(--sand-200);border-color:var(--terracotta-500);color:var(--terracotta-600);}
        .add-tile svg{width:26px;height:26px;}


        /* Reviews */
        .review-card{display:flex;gap:14px;background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);padding:18px;box-shadow:var(--shadow-sm);margin-bottom:14px;}
        .review-avatar{width:44px;height:44px;border-radius:50%;flex:none;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:15px;background:linear-gradient(155deg,var(--terracotta-600),var(--gold-500));}
        .review-top{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:4px;flex-wrap:wrap;}
        .review-name{font-weight:700;color:var(--coffee-900);font-size:14.5px;}
        .review-tour{font-size:12px;color:var(--ink-soft);margin-top:1px;}
        .review-text{font-size:13.8px;color:var(--coffee-800);line-height:1.55;margin:8px 0 10px;}
        .review-actions{display:flex;gap:8px;}


        /* Messages */
        .msg-layout{display:grid;grid-template-columns:340px 1fr;gap:18px;align-items:start;}
        .msg-list{background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);overflow:hidden;box-shadow:var(--shadow-sm);}
        .msg-item{display:flex;gap:11px;padding:14px 16px;border-bottom:1px solid var(--line);cursor:pointer;position:relative;}
        .msg-item:hover{background:var(--sand-50);}
        .msg-item.active{background:var(--terracotta-100);}
        .msg-item.unread::before{content:"";position:absolute;left:6px;top:50%;transform:translateY(-50%);width:7px;height:7px;border-radius:50%;background:var(--terracotta-600);}
        .msg-item-name{font-weight:700;font-size:13.5px;color:var(--coffee-900);}
        .msg-item-prev{font-size:12px;color:var(--ink-soft);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:230px;}
        .msg-item-time{font-size:10.5px;color:var(--ink-soft);margin-left:auto;flex:none;}
        .msg-detail{background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);padding:24px;min-height:420px;display:flex;flex-direction:column;}
        .msg-detail-head{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1px solid var(--line);padding-bottom:16px;margin-bottom:16px;}
        .msg-detail-body{font-size:14.5px;line-height:1.7;color:var(--coffee-800);flex:1;}
        .msg-detail-foot{display:flex;gap:10px;margin-top:20px;padding-top:16px;border-top:1px solid var(--line);}


        /* Settings */
        .settings-grid{display:grid;grid-template-columns:240px 1fr;gap:24px;align-items:start;}
        .settings-grid-single{grid-template-columns:1fr;}
        .settings-nav{display:flex;flex-direction:column;gap:3px;background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);padding:10px;box-shadow:var(--shadow-sm);}
        .settings-nav button{
            display:flex;align-items:center;gap:10px;text-align:left;padding:11px 13px;border-radius:9px;border:none;background:transparent;
            font-size:13.8px;font-weight:600;color:var(--coffee-700);cursor:pointer;
        }
        .settings-nav button.active{background:var(--sand-100);color:var(--terracotta-600);}
        .settings-nav button svg{width:17px;height:17px;}
        .settings-panel{background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);padding:26px;}
        .settings-panel h3{font-size:17px;margin-bottom:18px;}
        .settings-pane{display:none;}
        .settings-pane.active{display:block;}
        .field{margin-bottom:16px;}
        .field label{display:block;font-size:12.5px;font-weight:600;color:var(--coffee-700);margin-bottom:7px;text-transform:uppercase;letter-spacing:.04em;}
        .field input,.field select,.field textarea{
            width:100%;padding:12px 14px;border:1.5px solid var(--line);border-radius:var(--radius-sm);
            background:var(--white);font-size:14.5px;color:var(--ink);transition:border-color .15s, box-shadow .15s;
        }
        .field input:focus,.field select:focus,.field textarea:focus{
            outline:none;border-color:var(--terracotta-500);box-shadow:0 0 0 3px var(--terracotta-100);
        }
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
        .toggle-row{display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid var(--line);}
        .toggle-row:last-child{border-bottom:none;}
        .toggle-text strong{display:block;font-size:14px;color:var(--coffee-900);margin-bottom:2px;}
        .toggle-text span{font-size:12.5px;color:var(--ink-soft);}
        .switch{width:42px;height:24px;border-radius:20px;background:var(--sand-200);position:relative;flex:none;border:none;cursor:pointer;transition:background .2s;}
        .switch::after{content:"";position:absolute;top:3px;left:3px;width:18px;height:18px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.25);transition:transform .2s;}
        .switch.on{background:var(--acacia-500);}
        .switch.on::after{transform:translateX(18px);}


        /* Modal */
        .modal-backdrop{
            position:fixed;inset:0;background:rgba(36,20,8,.5);backdrop-filter:blur(2px);
            display:none;align-items:flex-start;justify-content:center;z-index:400;padding:40px 20px;overflow-y:auto;
        }
        .modal-backdrop.show{display:flex;}
        .modal{
            background:var(--sand-50);border-radius:var(--radius-lg);width:100%;max-width:560px;
            box-shadow:var(--shadow-lg);animation:riseIn .3s cubic-bezier(.2,.8,.2,1);margin:auto;
        }
        @keyframes riseIn{from{opacity:0;transform:translateY(14px);}to{opacity:1;transform:translateY(0);}}
        .modal-head{display:flex;align-items:center;justify-content:space-between;padding:22px 24px;border-bottom:1px solid var(--line);}
        .modal-head h3{font-size:19px;}
        .modal-close{width:34px;height:34px;border-radius:9px;border:1px solid var(--line);background:var(--white);display:flex;align-items:center;justify-content:center;}
        .modal-body{padding:22px 24px;max-height:60vh;overflow-y:auto;}
        .modal-foot{display:flex;justify-content:flex-end;gap:10px;padding:18px 24px;border-top:1px solid var(--line);}


        /* Toast */
        #toastHost{position:fixed;bottom:24px;right:24px;z-index:600;display:flex;flex-direction:column;gap:10px;}
        .toast{
            background:var(--coffee-900);color:#fff;padding:13px 18px;border-radius:11px;font-size:13.5px;font-weight:600;
            box-shadow:var(--shadow-lg);display:flex;align-items:center;gap:10px;min-width:240px;animation:toastIn .3s ease;
        }
        .toast.success{background:var(--acacia-600);}
        .toast.error{background:var(--danger);}
        .toast svg{width:17px;height:17px;flex:none;}
        @keyframes toastIn{from{opacity:0;transform:translateX(20px);}to{opacity:1;transform:translateX(0);}}


        /* Responsive */
        .mobile-overlay{position:fixed;inset:0;background:rgba(36,20,8,.45);z-index:190;display:none;}
        .mobile-overlay.show{display:block;}
        @media (max-width:1180px){
            .stat-grid{grid-template-columns:repeat(2,1fr);}
            .panel-grid{grid-template-columns:1fr;}
            .settings-grid{grid-template-columns:1fr;}
            .msg-layout{grid-template-columns:1fr;}
        }
        @media (max-width:900px){
            .sidebar{transform:translateX(-100%);width:var(--sidebar-w);z-index:300;}
            .sidebar.mobile-open{transform:translateX(0);}
            .main{margin-left:0 !important;}
            .tb-search{display:none;}
        }
        @media (max-width:640px){
            .stat-grid{grid-template-columns:1fr;}
            .view-wrap{padding:16px;}
            .topbar{padding:0 14px;gap:10px;}
            .form-row{grid-template-columns:1fr;}
            .tb-live span{display:none;}
            .view-head h2{font-size:22px;}
        }
        @media (prefers-reduced-motion:reduce){
            *{animation-duration:.001ms !important;transition-duration:.001ms !important;}
        }
    </style>
</head>
<body>
    <div id="app" class="show">
        <div class="mobile-overlay" id="mobileOverlay" onclick="closeMobileSidebar()"></div>
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sb-brand">
                <img src="https://res.cloudinary.com/aenplcpl/image/upload/f_auto,q_auto,w_200/v1782890324/safari-logo-white_bexcal.png" alt="Tanzania Daily Tours & Safari" style="width: 38px; height: 38px; border-radius: 10px; flex: none;">
                <div class="sb-brand-text">
                    <strong>Tanzania Daily</strong>
                    <span>Tours & Safari · CMS</span>
                </div>
            </div>
            <nav class="sb-nav">
                <div class="sb-section-label">Overview</div>
                <a href="https://tanzaniadailytoursandsafari.com/live" class="sb-item ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="9" rx="1.5"></rect>
                        <rect x="14" y="3" width="7" height="5" rx="1.5"></rect>
                        <rect x="14" y="12" width="7" height="9" rx="1.5"></rect>
                        <rect x="3" y="16" width="7" height="5" rx="1.5"></rect>
                    </svg>
                    <span>Dashboard</span>
                </a>
                <div class="sb-section-label">Content</div>
                <a href="https://tanzaniadailytoursandsafari.com/live/destinations" class="sb-item ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 11 12 4l9 7"></path>
                        <path d="M5 10v10h14V10"></path>
                        <path d="M9 20v-6h6v6"></path>
                    </svg>
                    <span>Destinations</span>
                    <span class="badge" id="navDestCount">62</span>
                </a>
                <a href="https://tanzaniadailytoursandsafari.com/live/gallery" class="sb-item ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.8"></circle>
                        <path d="m21 15-5-5L5 21"></path>
                    </svg>
                    <span>Gallery</span>
                </a>
                <a href="https://tanzaniadailytoursandsafari.com/live/reviews" class="sb-item ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2.5 15.1 9 22 10 17 15 18.2 22 12 18.6 5.8 22 7 15 2 10 8.9 9"></polygon>
                    </svg>
                    <span>Reviews</span>
                    <span class="badge" id="navReviewCount">0</span>
                </a>
                <div class="sb-section-label">Operations</div>
                <a href="https://tanzaniadailytoursandsafari.com/live/bookings" class="sb-item ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="17" rx="2"></rect>
                        <path d="M16 2v4M8 2v4M3 9h18"></path>
                        <path d="m9 14 2 2 4-4"></path>
                    </svg>
                    <span>Bookings</span>
                    <span class="badge" id="navBookingCount">1</span>
                </a>
                <a href="https://tanzaniadailytoursandsafari.com/live/payments" class="sb-item active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                        <line x1="2" y1="10" x2="22" y2="10"></line>
                    </svg>
                    <span>Payments</span>
                    <span class="badge" id="navPaymentCount">5</span>
                </a>
                <a href="https://tanzaniadailytoursandsafari.com/live/messages" class="sb-item ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2Z"></path>
                    </svg>
                    <span>Messages</span>
                    <span class="badge" id="navMsgCount">0</span>
                </a>
                <div class="sb-section-label">System</div>
                <div class="sb-drop ">
                    <button type="button" class="sb-item sb-drop-toggle " onclick="toggleSbDrop(this)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1.04 1.56V21a2 2 0 0 1-4 0v-.09A1.7 1.7 0 0 0 9 19.4a1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.56-1.04H3a2 2 0 0 1 0-4h.09A1.7 1.7 0 0 0 4.6 9a1.7 1.7 0 0 0-.34-1.87l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1.04-1.56V3a2 2 0 0 1 4 0v.09A1.7 1.7 0 0 0 15 4.6a1.7 1.7 0 0 0 1.87-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9a1.7 1.7 0 0 0 1.56 1.04H21a2 2 0 0 1 0 4h-.09A1.7 1.7 0 0 0 19.4 15Z"></path>
                        </svg>
                        <span>Site Settings</span>
                        <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <div class="sb-drop-menu">
                        <a href="https://tanzaniadailytoursandsafari.com/live/settings?pane=general" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                                <rect x="9" y="9" width="6" height="6"></rect>
                            </svg>
                            General
                        </a>
                        <a href="https://tanzaniadailytoursandsafari.com/live/settings?pane=brand" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="13.5" cy="6.5" r=".5"></circle>
                                <circle cx="17.5" cy="10.5" r=".5"></circle>
                                <circle cx="8.5" cy="7.5" r=".5"></circle>
                                <circle cx="6.5" cy="12.5" r=".5"></circle>
                                <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C22 6.012 17.461 2 12 2Z"></path>
                            </svg>
                            Brand & Colors
                        </a>
                        <a href="https://tanzaniadailytoursandsafari.com/live/settings?pane=contact" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92Z"></path>
                            </svg>
                            Contact & Social
                        </a>
                        <a href="https://tanzaniadailytoursandsafari.com/live/settings?pane=notifications" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path>
                                <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path>
                            </svg>
                            Notifications
                        </a>
                        <a href="https://tanzaniadailytoursandsafari.com/live/users" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            Admin Users
                        </a>
                        <a href="https://tanzaniadailytoursandsafari.com/live/settings?pane=payments" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                                <line x1="2" y1="10" x2="22" y2="10"></line>
                            </svg>
                            Payments
                        </a>
                        <a href="https://tanzaniadailytoursandsafari.com/live/settings?pane=mail" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                            </svg>
                            Mail
                        </a>
                    </div>
                </div>
            </nav>
            <div class="sb-footer">
                <a href="https://tanzaniadailytoursandsafari.com/live/profile" class="sb-user" style="text-decoration:none;">
                    <div class="sb-avatar">
                        JE
                    </div>
                    <div class="sb-user-text">
                        <strong>Jeremia Developer</strong>
                        <span>jeremiat449@gmail.com</span>
                    </div>
                </a>
            </div>
        </aside>

        <!-- Main -->
        <div class="main" id="mainArea">
            <header class="topbar">
                <button class="tb-toggle" onclick="toggleSidebar()" aria-label="Toggle sidebar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="4" y1="7" x2="20" y2="7"></line>
                        <line x1="4" y1="12" x2="20" y2="12"></line>
                        <line x1="4" y1="17" x2="20" y2="17"></line>
                    </svg>
                </button>
                <div class="tb-search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" placeholder="Search tours, bookings, guests…">
                </div>
                <div class="tb-right">
                    <div class="tb-live"><span>Site live</span></div>
                    <a class="tb-iconbtn" href="https://tanzaniadailytoursandsafari.com" target="_blank" rel="noopener" aria-label="View live site" title="View live site">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                            <path d="M15 3h6v6"></path>
                            <path d="M10 14 21 3"></path>
                        </svg>
                    </a>
                    <form method="POST" action="https://tanzaniadailytoursandsafari.com/live/logout" style="display:inline;">
                        <input type="hidden" name="_token" value="w3Vlfvw2Bh8VfqolGpMryy5mbgT3npZg5cr409mV" autocomplete="off">                        <button type="submit" class="tb-iconbtn" aria-label="Log out" title="Log out">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16 17 21 12 16 7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                        </button>
                    </form>
                </div>
            </header>

            <div class="view-wrap">
                <!-- Session Messages -->
                
                <!-- Content -->
                <div class="view active">
    <div class="view-head">
        <div>
            <h2>Payments</h2>
            <p class="sub">Track, verify and manage all PesaPal transactions.</p>
        </div>
        <div class="view-actions">
            <a href="https://tanzaniadailytoursandsafari.com/live/payments/settings" class="btn btn-soft">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1.04 1.56V21a2 2 0 0 1-4 0v-.09A1.7 1.7 0 0 0 9 19.4a1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.56-1.04H3a2 2 0 0 1 0-4h.09A1.7 1.7 0 0 0 4.6 9a1.7 1.7 0 0 0-.34-1.87l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1.04-1.56V3a2 2 0 0 1 4 0v.09A1.7 1.7 0 0 0 15 4.6a1.7 1.7 0 0 0 1.87-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9a1.7 1.7 0 0 0 1.56 1.04H21a2 2 0 0 1 0 4h-.09A1.7 1.7 0 0 0 19.4 15Z"></path>
                </svg>
                Payment Settings
            </a>
        </div>
    </div>

    <div class="table-card">
        <div class="table-toolbar">
            <div class="chip-filters">
                <a class="chip active" href="https://tanzaniadailytoursandsafari.com/live/payments">All</a>
                                    <a class="chip " href="https://tanzaniadailytoursandsafari.com/live/payments?status=pending">Pending</a>
                                    <a class="chip " href="https://tanzaniadailytoursandsafari.com/live/payments?status=processing">Processing</a>
                                    <a class="chip " href="https://tanzaniadailytoursandsafari.com/live/payments?status=completed">Completed</a>
                                    <a class="chip " href="https://tanzaniadailytoursandsafari.com/live/payments?status=failed">Failed</a>
                                    <a class="chip " href="https://tanzaniadailytoursandsafari.com/live/payments?status=cancelled">Cancelled</a>
                                    <a class="chip " href="https://tanzaniadailytoursandsafari.com/live/payments?status=refunded">Refunded</a>
                                    <a class="chip " href="https://tanzaniadailytoursandsafari.com/live/payments?status=expired">Expired</a>
                            </div>
            <form class="table-search" method="GET" action="https://tanzaniadailytoursandsafari.com/live/payments">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" name="q" value="" placeholder="Search reference, email, guest…">
                            </form>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Payment</th>
                        <th>Customer</th>
                        <th>Booking</th>
                        <th>Amount</th>
                        <th>Mode</th>
                        <th>Method</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                                                                    <tr>
                            <td>
                                <div class="cell-main">
                                    <div>
                                        <div class="cell-title">TDTS-38A06363</div>
                                        <div class="cell-sub">no tracking id</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="cell-title">David Ngungila</div>
                                <div class="cell-sub">davidngungila@gmail.com</div>
                            </td>
                            <td>
                                <div class="cell-title">TDTS-00026</div>
                                <div class="cell-sub">Chemka Hot Springs — booking TDTS-00026</div>
                            </td>
                            <td>
                                <div class="cell-title">TSh68,580</div>
                                                                    <div class="cell-sub">of TSh228,600</div>
                                                            </td>
                            <td>
                                <span class="tag tag-grey">Deposit 30%</span>
                            </td>
                            <td>—</td>
                            <td>Sep 13, 2026</td>
                            <td><span class="tag tag-gold">Pending</span></td>
                            <td>
                                <div class="row-actions">
                                    <a href="https://tanzaniadailytoursandsafari.com/live/payments/7" title="View" style="width:32px;height:32px;border-radius:8px;border:1px solid var(--line);background:var(--white);display:flex;align-items:center;justify-content:center;color:var(--coffee-700);">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14.5px;height:14.5px;">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </a>
                                                                            <form action="https://tanzaniadailytoursandsafari.com/live/payments/7/verify" method="POST" title="Re-verify with PesaPal" style="display:inline;">
                                            <input type="hidden" name="_token" value="w3Vlfvw2Bh8VfqolGpMryy5mbgT3npZg5cr409mV" autocomplete="off">                                            <button type="submit" style="width:32px;height:32px;border-radius:8px;border:1px solid var(--line);background:var(--white);display:flex;align-items:center;justify-content:center;color:var(--coffee-700);">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14.5px;height:14.5px;">
                                                    <path d="M21 12a9 9 0 1 1-2.64-6.36"></path>
                                                    <polyline points="21 3 21 9 15 9"></polyline>
                                                </svg>
                                            </button>
                                        </form>
                                                                    </div>
                            </td>
                        </tr>
                                                <tr>
                            <td>
                                <div class="cell-main">
                                    <div>
                                        <div class="cell-title">TDTS-670195B3</div>
                                        <div class="cell-sub">no tracking id</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="cell-title">Kija Mpunzi</div>
                                <div class="cell-sub">jeremiat449@gmail.com</div>
                            </td>
                            <td>
                                <div class="cell-title">—</div>
                                <div class="cell-sub">Mount Kilimanjaro Climb 6 Days Umbwe Rou...</div>
                            </td>
                            <td>
                                <div class="cell-title">$585</div>
                                                                    <div class="cell-sub">of $1,950</div>
                                                            </td>
                            <td>
                                <span class="tag tag-grey">Deposit 30%</span>
                            </td>
                            <td>—</td>
                            <td>Sep 07, 2026</td>
                            <td><span class="tag tag-gold">Pending</span></td>
                            <td>
                                <div class="row-actions">
                                    <a href="https://tanzaniadailytoursandsafari.com/live/payments/6" title="View" style="width:32px;height:32px;border-radius:8px;border:1px solid var(--line);background:var(--white);display:flex;align-items:center;justify-content:center;color:var(--coffee-700);">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14.5px;height:14.5px;">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </a>
                                                                            <form action="https://tanzaniadailytoursandsafari.com/live/payments/6/verify" method="POST" title="Re-verify with PesaPal" style="display:inline;">
                                            <input type="hidden" name="_token" value="w3Vlfvw2Bh8VfqolGpMryy5mbgT3npZg5cr409mV" autocomplete="off">                                            <button type="submit" style="width:32px;height:32px;border-radius:8px;border:1px solid var(--line);background:var(--white);display:flex;align-items:center;justify-content:center;color:var(--coffee-700);">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14.5px;height:14.5px;">
                                                    <path d="M21 12a9 9 0 1 1-2.64-6.36"></path>
                                                    <polyline points="21 3 21 9 15 9"></polyline>
                                                </svg>
                                            </button>
                                        </form>
                                                                    </div>
                            </td>
                        </tr>
                                                <tr>
                            <td>
                                <div class="cell-main">
                                    <div>
                                        <div class="cell-title">TDTS-B3030CFA</div>
                                        <div class="cell-sub">no tracking id</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="cell-title">Ally</div>
                                <div class="cell-sub">ally0623975@gmail.com</div>
                            </td>
                            <td>
                                <div class="cell-title">—</div>
                                <div class="cell-sub">3-Day Classic Tarangire &amp; Ngorongoro Cra...</div>
                            </td>
                            <td>
                                <div class="cell-title">$2,687</div>
                                                                    <div class="cell-sub">of $8,955</div>
                                                            </td>
                            <td>
                                <span class="tag tag-grey">Deposit 30%</span>
                            </td>
                            <td>—</td>
                            <td>Sep 07, 2026</td>
                            <td><span class="tag tag-red">Failed</span></td>
                            <td>
                                <div class="row-actions">
                                    <a href="https://tanzaniadailytoursandsafari.com/live/payments/5" title="View" style="width:32px;height:32px;border-radius:8px;border:1px solid var(--line);background:var(--white);display:flex;align-items:center;justify-content:center;color:var(--coffee-700);">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14.5px;height:14.5px;">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </a>
                                                                            <form action="https://tanzaniadailytoursandsafari.com/live/payments/5/verify" method="POST" title="Re-verify with PesaPal" style="display:inline;">
                                            <input type="hidden" name="_token" value="w3Vlfvw2Bh8VfqolGpMryy5mbgT3npZg5cr409mV" autocomplete="off">                                            <button type="submit" style="width:32px;height:32px;border-radius:8px;border:1px solid var(--line);background:var(--white);display:flex;align-items:center;justify-content:center;color:var(--coffee-700);">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14.5px;height:14.5px;">
                                                    <path d="M21 12a9 9 0 1 1-2.64-6.36"></path>
                                                    <polyline points="21 3 21 9 15 9"></polyline>
                                                </svg>
                                            </button>
                                        </form>
                                                                    </div>
                            </td>
                        </tr>
                                                <tr>
                            <td>
                                <div class="cell-main">
                                    <div>
                                        <div class="cell-title">TDTS-B70B325B</div>
                                        <div class="cell-sub">no tracking id</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="cell-title">Kija Mpunzi</div>
                                <div class="cell-sub">jeremiat449@gmail.com</div>
                            </td>
                            <td>
                                <div class="cell-title">—</div>
                                <div class="cell-sub">Mount Kilimanjaro Climb 6 Days Umbwe Rou...</div>
                            </td>
                            <td>
                                <div class="cell-title">$585</div>
                                                                    <div class="cell-sub">of $1,950</div>
                                                            </td>
                            <td>
                                <span class="tag tag-grey">Deposit 30%</span>
                            </td>
                            <td>—</td>
                            <td>Sep 06, 2026</td>
                            <td><span class="tag tag-red">Failed</span></td>
                            <td>
                                <div class="row-actions">
                                    <a href="https://tanzaniadailytoursandsafari.com/live/payments/4" title="View" style="width:32px;height:32px;border-radius:8px;border:1px solid var(--line);background:var(--white);display:flex;align-items:center;justify-content:center;color:var(--coffee-700);">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14.5px;height:14.5px;">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </a>
                                                                            <form action="https://tanzaniadailytoursandsafari.com/live/payments/4/verify" method="POST" title="Re-verify with PesaPal" style="display:inline;">
                                            <input type="hidden" name="_token" value="w3Vlfvw2Bh8VfqolGpMryy5mbgT3npZg5cr409mV" autocomplete="off">                                            <button type="submit" style="width:32px;height:32px;border-radius:8px;border:1px solid var(--line);background:var(--white);display:flex;align-items:center;justify-content:center;color:var(--coffee-700);">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14.5px;height:14.5px;">
                                                    <path d="M21 12a9 9 0 1 1-2.64-6.36"></path>
                                                    <polyline points="21 3 21 9 15 9"></polyline>
                                                </svg>
                                            </button>
                                        </form>
                                                                    </div>
                            </td>
                        </tr>
                                                <tr>
                            <td>
                                <div class="cell-main">
                                    <div>
                                        <div class="cell-title">TDTS-9F87BB77</div>
                                        <div class="cell-sub">no tracking id</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="cell-title">Masanja Hassan</div>
                                <div class="cell-sub">jeremiat449@gmail.com</div>
                            </td>
                            <td>
                                <div class="cell-title">—</div>
                                <div class="cell-sub">Mount Kilimanjaro Climb 6 Days Umbwe Rou...</div>
                            </td>
                            <td>
                                <div class="cell-title">$585</div>
                                                                    <div class="cell-sub">of $1,950</div>
                                                            </td>
                            <td>
                                <span class="tag tag-grey">Deposit 30%</span>
                            </td>
                            <td>—</td>
                            <td>Sep 06, 2026</td>
                            <td><span class="tag tag-gold">Pending</span></td>
                            <td>
                                <div class="row-actions">
                                    <a href="https://tanzaniadailytoursandsafari.com/live/payments/3" title="View" style="width:32px;height:32px;border-radius:8px;border:1px solid var(--line);background:var(--white);display:flex;align-items:center;justify-content:center;color:var(--coffee-700);">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14.5px;height:14.5px;">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </a>
                                                                            <form action="https://tanzaniadailytoursandsafari.com/live/payments/3/verify" method="POST" title="Re-verify with PesaPal" style="display:inline;">
                                            <input type="hidden" name="_token" value="w3Vlfvw2Bh8VfqolGpMryy5mbgT3npZg5cr409mV" autocomplete="off">                                            <button type="submit" style="width:32px;height:32px;border-radius:8px;border:1px solid var(--line);background:var(--white);display:flex;align-items:center;justify-content:center;color:var(--coffee-700);">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14.5px;height:14.5px;">
                                                    <path d="M21 12a9 9 0 1 1-2.64-6.36"></path>
                                                    <polyline points="21 3 21 9 15 9"></polyline>
                                                </svg>
                                            </button>
                                        </form>
                                                                    </div>
                            </td>
                        </tr>
                                                <tr>
                            <td>
                                <div class="cell-main">
                                    <div>
                                        <div class="cell-title">TDTS-46C54852</div>
                                        <div class="cell-sub">no tracking id</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="cell-title">Jeremia Thomas</div>
                                <div class="cell-sub">jeremiat449@gmail.com</div>
                            </td>
                            <td>
                                <div class="cell-title">—</div>
                                <div class="cell-sub">5 Day Kilimanjaro Marangu Route and 2 Da...</div>
                            </td>
                            <td>
                                <div class="cell-title">TSh1,333,500</div>
                                                                    <div class="cell-sub">of TSh4,445,000</div>
                                                            </td>
                            <td>
                                <span class="tag tag-grey">Deposit 30%</span>
                            </td>
                            <td>—</td>
                            <td>Sep 06, 2026</td>
                            <td><span class="tag tag-gold">Pending</span></td>
                            <td>
                                <div class="row-actions">
                                    <a href="https://tanzaniadailytoursandsafari.com/live/payments/2" title="View" style="width:32px;height:32px;border-radius:8px;border:1px solid var(--line);background:var(--white);display:flex;align-items:center;justify-content:center;color:var(--coffee-700);">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14.5px;height:14.5px;">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </a>
                                                                            <form action="https://tanzaniadailytoursandsafari.com/live/payments/2/verify" method="POST" title="Re-verify with PesaPal" style="display:inline;">
                                            <input type="hidden" name="_token" value="w3Vlfvw2Bh8VfqolGpMryy5mbgT3npZg5cr409mV" autocomplete="off">                                            <button type="submit" style="width:32px;height:32px;border-radius:8px;border:1px solid var(--line);background:var(--white);display:flex;align-items:center;justify-content:center;color:var(--coffee-700);">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14.5px;height:14.5px;">
                                                    <path d="M21 12a9 9 0 1 1-2.64-6.36"></path>
                                                    <polyline points="21 3 21 9 15 9"></polyline>
                                                </svg>
                                            </button>
                                        </form>
                                                                    </div>
                            </td>
                        </tr>
                                                <tr>
                            <td>
                                <div class="cell-main">
                                    <div>
                                        <div class="cell-title">TDTS-C9B96E87</div>
                                        <div class="cell-sub">cf968ccc-02ab-4607-8579-d9ee869f93ba</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="cell-title">JEREMIA THOMAS</div>
                                <div class="cell-sub">jeremiat449@gmail.com</div>
                            </td>
                            <td>
                                <div class="cell-title">—</div>
                                <div class="cell-sub">3-Day Classic Tarangire &amp; Ngorongoro Cra...</div>
                            </td>
                            <td>
                                <div class="cell-title">TSh299</div>
                                                                    <div class="cell-sub">of TSh995</div>
                                                            </td>
                            <td>
                                <span class="tag tag-grey">Deposit 30%</span>
                            </td>
                            <td>—</td>
                            <td>Sep 06, 2026</td>
                            <td><span class="tag tag-gold">Pending</span></td>
                            <td>
                                <div class="row-actions">
                                    <a href="https://tanzaniadailytoursandsafari.com/live/payments/1" title="View" style="width:32px;height:32px;border-radius:8px;border:1px solid var(--line);background:var(--white);display:flex;align-items:center;justify-content:center;color:var(--coffee-700);">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14.5px;height:14.5px;">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </a>
                                                                            <form action="https://tanzaniadailytoursandsafari.com/live/payments/1/verify" method="POST" title="Re-verify with PesaPal" style="display:inline;">
                                            <input type="hidden" name="_token" value="w3Vlfvw2Bh8VfqolGpMryy5mbgT3npZg5cr409mV" autocomplete="off">                                            <button type="submit" style="width:32px;height:32px;border-radius:8px;border:1px solid var(--line);background:var(--white);display:flex;align-items:center;justify-content:center;color:var(--coffee-700);">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14.5px;height:14.5px;">
                                                    <path d="M21 12a9 9 0 1 1-2.64-6.36"></path>
                                                    <polyline points="21 3 21 9 15 9"></polyline>
                                                </svg>
                                            </button>
                                        </form>
                                                                    </div>
                            </td>
                        </tr>
                                                            </tbody>
            </table>
        </div>
            </div>
</div>

            </div>
        </div>
    </div>
    <div id="toastHost"></div>
    <script>
        // Auto-logout timer (5 minutes in milliseconds)
        const AUTO_LOGOUT_TIME = 5 * 60 * 1000;
        let autoLogoutTimer;

        // Reset the auto-logout timer on user activity
        function resetAutoLogoutTimer() {
            clearTimeout(autoLogoutTimer);
            autoLogoutTimer = setTimeout(() => {
                // Clear the session on the server side by redirecting to login
                window.location.href = "https://tanzaniadailytoursandsafari.com/live/login";
            }, AUTO_LOGOUT_TIME);
        }

        // Add event listeners for user activity
        ['click', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
            document.addEventListener(event, resetAutoLogoutTimer, true);
        });

        // Initialize the timer when the page loads
        resetAutoLogoutTimer();

        function toggleSidebar(){
            if(window.innerWidth <= 900){
                document.getElementById('sidebar').classList.toggle('mobile-open');
                document.getElementById('mobileOverlay').classList.toggle('show');
            } else {
                document.getElementById('sidebar').classList.toggle('collapsed');
            }
        }
        function closeMobileSidebar(){
            document.getElementById('sidebar').classList.remove('mobile-open');
            document.getElementById('mobileOverlay').classList.remove('show');
        }
        function toggleSbDrop(el){
            const drop = el.closest('.sb-drop');
            const wasOpen = drop.classList.contains('open');
            document.querySelectorAll('.sb-drop').forEach(d => d.classList.remove('open'));
            if(!wasOpen) drop.classList.add('open');
        }

        function toast(msg, type='default'){
            const host = document.getElementById('toastHost');
            const el = document.createElement('div');
            el.className = 'toast ' + (type==='success'?'success':type==='error'?'error':'');
            el.innerHTML = (type==='success' ? '<svg viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><path d=\"M20 6 9 17l-5-5\"></path></svg>' : '') + '<span>'+msg+'</span>';
            host.appendChild(el);
            setTimeout(()=>{ el.style.opacity='0'; el.style.transform='translateX(20px)'; el.style.transition='all .25s'; setTimeout(()=>el.remove(),250); }, 3000);
        }

        function openModal(id){ document.getElementById(id).classList.add('show'); }
        function closeModal(id){ document.getElementById(id).classList.remove('show'); }
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="w3Vlfvw2Bh8VfqolGpMryy5mbgT3npZg5cr409mV">
    <title>Settings · Tanzania Daily Tours & Safari</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        :root{
            --sand-50:#FBF7EF;
            --sand-100:#F4ECDC;
            --sand-200:#E9DCC0;
            --coffee-900:#2A1B10;
            --coffee-800:#3B2718;
            --coffee-700:#4D3422;
            --coffee-500:#7A5C42;
            --coffee-300:#A98968;
            --terracotta-600:#C2592B;
            --terracotta-500:#D06B3A;
            --terracotta-100:#F6E1D3;
            --acacia-600:#5E6E3F;
            --acacia-500:#7A8450;
            --acacia-100:#E2E7D4;
            --gold-500:#D4A24C;
            --gold-100:#F7E9CB;
            --ink:#241408;
            --ink-soft:#6B5A48;
            --line:#E4D7C2;
            --white:#FFFFFF;
            --danger:#B33A3A;
            --danger-100:#F6DCDA;
            --success:#3F6B3F;
            --radius-sm:8px;
            --radius-md:14px;
            --radius-lg:20px;
            --shadow-sm:0 1px 2px rgba(42,27,16,.08);
            --shadow-md:0 8px 24px rgba(42,27,16,.10);
            --shadow-lg:0 20px 48px rgba(42,27,16,.18);
            --sidebar-w:264px;
            --sidebar-w-collapsed:76px;
            --topbar-h:72px;
        }

        *{box-sizing:border-box;}
        html,body{height:100%;}
        body{
            margin:0;
            font-family:'Raleway',sans-serif;
            background:var(--sand-50);
            color:var(--ink);
            -webkit-font-smoothing:antialiased;
            overflow-x:hidden;
        }
        ::selection{background:var(--terracotta-100);color:var(--coffee-900);}
        h1,h2,h3,h4{font-family:'Raleway',sans-serif;margin:0;color:var(--coffee-900);letter-spacing:-0.01em;}
        p{margin:0;}
        a{color:inherit;text-decoration:none;}
        button{font-family:inherit;cursor:pointer;}
        input,select,textarea{font-family:inherit;}
        .mono{font-family:'Raleway',sans-serif;}
        ::-webkit-scrollbar{width:9px;height:9px;}
        ::-webkit-scrollbar-track{background:transparent;}
        ::-webkit-scrollbar-thumb{background:var(--coffee-300);border-radius:10px;}
        ::-webkit-scrollbar-thumb:hover{background:var(--coffee-500);}


        /* Sidebar */
        .sidebar{
            position:fixed;top:0;left:0;bottom:0;width:var(--sidebar-w);z-index:200;
            background:var(--coffee-900);
            background-image:radial-gradient(circle at 0% 0%, rgba(212,162,76,.10), transparent 55%);
            display:flex;flex-direction:column;
            transition:width .25s ease, transform .25s ease;
            border-right:1px solid rgba(255,255,255,.06);
        }
        .sidebar.collapsed{width:var(--sidebar-w-collapsed);}
        .sb-brand{
            display:flex;align-items:center;gap:12px;padding:22px 20px;
            border-bottom:1px solid rgba(255,255,255,.08);min-height:var(--topbar-h);
        }
        .sb-mark{
            width:38px;height:38px;border-radius:10px;flex:none;
            background:linear-gradient(155deg,var(--terracotta-600),var(--gold-500));
            display:flex;align-items:center;justify-content:center;box-shadow:var(--shadow-sm);
        }
        .sb-mark svg{width:21px;height:21px;}
        .sb-brand-text{overflow:hidden;white-space:nowrap;}
        .sb-brand-text strong{display:block;color:#fff;font-family:'Raleway',sans-serif;font-size:15.5px;line-height:1.2;}
        .sb-brand-text span{display:block;color:var(--gold-500);font-size:11px;letter-spacing:.06em;text-transform:uppercase;font-weight:600;}
        .sidebar.collapsed .sb-brand-text{display:none;}
        .sb-nav{flex:1;overflow-y:auto;padding:16px 12px;}
        .sb-section-label{
            color:rgba(255,255,255,.32);font-size:10.5px;font-weight:700;letter-spacing:.09em;
            text-transform:uppercase;padding:14px 12px 8px;
        }
        .sidebar.collapsed .sb-section-label{display:none;}
        .sb-item{
            display:flex;align-items:center;gap:13px;padding:11px 12px;border-radius:10px;
            color:rgba(255,255,255,.62);font-size:14px;font-weight:500;margin-bottom:2px;
            position:relative;transition:background .15s,color .15s;white-space:nowrap;
        }
        .sb-item:hover{background:rgba(255,255,255,.06);color:#fff;}
        .sb-item.active{background:rgba(212,162,76,.16);color:var(--gold-500);}
        .sb-item.active::before{
            content:"";position:absolute;left:-12px;top:8px;bottom:8px;width:3px;border-radius:3px;
            background:var(--gold-500);
        }
        .sb-item svg{width:19px;height:19px;flex:none;}
        .sb-item .badge{
            margin-left:auto;background:var(--terracotta-600);color:#fff;font-size:11px;font-weight:700;
            padding:1px 7px;border-radius:20px;
        }
        .sidebar.collapsed .sb-item span:not(.badge){display:none;}
        .sidebar.collapsed .sb-item .badge{display:none;}
        .sidebar.collapsed .sb-item{justify-content:center;}
        .sb-drop{position:relative;}
        .sb-drop-toggle{width:100%;cursor:pointer;background:none;border:none;font-family:inherit;}
        .sb-drop-toggle .chev{margin-left:auto;opacity:.55;transition:transform .25s ease;width:15px;height:15px;flex:none;}
        .sb-drop.open .sb-drop-toggle .chev{transform:rotate(180deg);}
        .sb-drop-menu{display:none;margin:2px 0 4px;padding-left:12px;}
        .sb-drop.open .sb-drop-menu{display:block;}
        .sidebar.collapsed .sb-drop-menu{display:none;}
        .sb-drop-sub{display:flex;align-items:center;gap:9px;padding:9px 12px;border-radius:8px;margin-bottom:1px;color:rgba(255,255,255,.55);font-size:13px;font-weight:500;text-decoration:none;transition:background .15s ease,color .15s ease;}
        .sb-drop-sub:hover{color:#fff;background:rgba(255,255,255,.06);}
        .sb-drop-sub.active{color:var(--gold-500);background:rgba(212,162,76,.12);}
        .sb-drop-sub svg{width:14px;height:14px;flex:none;}
        .sb-footer{padding:14px 20px 20px;border-top:1px solid rgba(255,255,255,.08);}
        .sb-user{display:flex;align-items:center;gap:11px;}
        .sb-avatar{
            width:36px;height:36px;border-radius:50%;flex:none;
            background:linear-gradient(155deg,var(--acacia-500),var(--acacia-600));
            display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:13px;
        }
        .sb-user-text{overflow:hidden;white-space:nowrap;}
        .sb-user-text strong{display:block;color:#fff;font-size:13.5px;}
        .sb-user-text span{display:block;color:rgba(255,255,255,.5);font-size:11.5px;}
        .sidebar.collapsed .sb-user-text{display:none;}


        /* Main */
        .main{margin-left:var(--sidebar-w);transition:margin-left .25s ease;min-height:100vh;display:flex;flex-direction:column;}
        .sidebar.collapsed ~ .main{margin-left:var(--sidebar-w-collapsed);}
        .topbar{
            position:sticky;top:0;z-index:100;height:var(--topbar-h);
            background:rgba(251,247,239,.86);backdrop-filter:blur(10px);
            border-bottom:1px solid var(--line);
            display:flex;align-items:center;gap:16px;padding:0 28px;
        }
        .tb-toggle{
            width:38px;height:38px;border-radius:10px;border:1.5px solid var(--line);background:var(--white);
            display:flex;align-items:center;justify-content:center;flex:none;
        }
        .tb-toggle svg{width:18px;height:18px;color:var(--coffee-700);}
        .tb-toggle:hover{background:var(--sand-100);}
        .tb-search{
            flex:1;max-width:420px;display:flex;align-items:center;gap:10px;
            background:var(--white);border:1.5px solid var(--line);border-radius:11px;padding:9px 14px;
        }
        .tb-search svg{width:17px;height:17px;color:var(--ink-soft);flex:none;}
        .tb-search input{border:none;outline:none;background:transparent;font-size:14px;width:100%;color:var(--ink);}
        .tb-right{margin-left:auto;display:flex;align-items:center;gap:10px;}
        .tb-iconbtn{
            width:40px;height:40px;border-radius:11px;border:1.5px solid var(--line);background:var(--white);
            display:flex;align-items:center;justify-content:center;position:relative;color:var(--coffee-700);
        }
        .tb-iconbtn:hover{background:var(--sand-100);}
        .tb-iconbtn svg{width:18px;height:18px;}
        .tb-dot{
            position:absolute;top:7px;right:7px;width:8px;height:8px;border-radius:50%;
            background:var(--terracotta-600);border:2px solid var(--sand-50);
        }
        .tb-live{
            display:flex;align-items:center;gap:7px;background:var(--acacia-100);color:var(--acacia-600);
            padding:7px 13px;border-radius:20px;font-size:12.5px;font-weight:700;
        }
        .tb-live::before{
            content:"";width:7px;height:7px;border-radius:50%;background:var(--acacia-600);box-shadow:0 0 0 0 rgba(94,110,63,.5);animation:pulse 2s infinite;
        }
        @keyframes pulse{
            0%{box-shadow:0 0 0 0 rgba(94,110,63,.45);}
            70%{box-shadow:0 0 0 7px rgba(94,110,63,0);}
            100%{box-shadow:0 0 0 0 rgba(94,110,63,0);}
        }
        .view-wrap{padding:28px;flex:1;}
        .view{display:none;animation:fadeUp .35s ease;}
        .view.active{display:block;}
        @keyframes fadeUp{from{opacity:0;transform:translateY(8px);}to{opacity:1;transform:translateY(0);}}
        .view-head{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:24px;flex-wrap:wrap;}
        .view-head h2{font-size:27px;}
        .view-head .sub{color:var(--ink-soft);font-size:14px;margin-top:5px;}
        .view-actions{display:flex;gap:10px;flex-wrap:wrap;}


        /* Stats */
        .stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:24px;}
        .stat-card{
            background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);
            padding:20px 20px 18px;box-shadow:var(--shadow-sm);position:relative;overflow:hidden;
        }
        .stat-card::after{content:"";position:absolute;right:-20px;top:-20px;width:90px;height:90px;border-radius:50%;background:var(--stat-tint,var(--terracotta-100));opacity:.5;}
        .stat-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
        .stat-icon{width:40px;height:40px;border-radius:11px;display:flex;align-items:center;justify-content:center;background:var(--stat-tint,var(--terracotta-100));color:var(--stat-fg,var(--terracotta-600));position:relative;}
        .stat-icon svg{width:20px;height:20px;}
        .stat-trend{font-size:12px;font-weight:700;padding:3px 8px;border-radius:20px;}
        .stat-trend.up{color:var(--success);background:var(--acacia-100);}
        .stat-trend.down{color:var(--danger);background:var(--danger-100);}
        .stat-value{font-family:'Raleway',sans-serif;font-size:30px;color:var(--coffee-900);position:relative;}
        .stat-label{font-size:13px;color:var(--ink-soft);margin-top:4px;position:relative;}

        .panel-grid{display:grid;grid-template-columns:1.55fr 1fr;gap:18px;margin-bottom:24px;}
        .panel{background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);overflow:hidden;}
        .panel-head{display:flex;align-items:center;justify-content:space-between;padding:18px 20px 14px;border-bottom:1px solid var(--line);}
        .panel-head h3{font-size:16.5px;}
        .panel-head .link{font-size:12.5px;font-weight:700;color:var(--terracotta-600);cursor:pointer;}
        .panel-body{padding:18px 20px 20px;}

        /* Bar Chart */
        .bars{display:flex;align-items:flex-end;gap:10px;height:170px;padding-top:10px;}
        .bar-col{flex:1;display:flex;flex-direction:column;align-items:center;gap:8px;height:100%;justify-content:flex-end;}
        .bar{
            width:100%;max-width:30px;border-radius:6px 6px 2px 2px;
            background:linear-gradient(180deg,var(--terracotta-500),var(--terracotta-600));
            transition:height .6s cubic-bezier(.2,.8,.2,1);position:relative;
        }
        .bar-col:nth-child(even) .bar{background:linear-gradient(180deg,var(--gold-500),#bb8636);}
        .bar-label{font-size:11px;color:var(--ink-soft);font-weight:600;}

        .donut-wrap{display:flex;align-items:center;gap:18px;}
        .legend{display:flex;flex-direction:column;gap:10px;flex:1;}
        .legend-item{display:flex;align-items:center;gap:9px;font-size:13px;}
        .legend-dot{width:10px;height:10px;border-radius:3px;flex:none;}
        .legend-item b{margin-left:auto;color:var(--coffee-900);}

        .activity-list{display:flex;flex-direction:column;gap:3px;}
        .activity-row{display:flex;gap:12px;padding:11px 0;border-bottom:1px dashed var(--line);}
        .activity-row:last-child{border-bottom:none;}
        .activity-ico{width:34px;height:34px;border-radius:9px;flex:none;display:flex;align-items:center;justify-content:center;background:var(--sand-100);color:var(--coffee-700);}
        .activity-ico svg{width:16px;height:16px;}
        .activity-text{font-size:13.5px;line-height:1.4;}
        .activity-text b{color:var(--coffee-900);}
        .activity-time{font-size:11.5px;color:var(--ink-soft);margin-top:2px;}


        /* Tables */
        .table-card{background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);overflow:hidden;}
        .table-toolbar{display:flex;align-items:center;gap:10px;padding:16px 18px;border-bottom:1px solid var(--line);flex-wrap:wrap;}
        .chip-filters{display:flex;gap:8px;flex-wrap:wrap;}
        .chip{padding:7px 14px;border-radius:20px;font-size:12.5px;font-weight:600;background:var(--sand-100);color:var(--coffee-700);border:1px solid transparent;cursor:pointer;display:inline-flex;align-items:center;text-decoration:none;}
        .chip.active{background:var(--coffee-900);color:#fff;}
        .table-search{display:flex;align-items:center;gap:8px;background:var(--sand-50);border:1.5px solid var(--line);border-radius:10px;padding:8px 12px;margin-left:auto;min-width:200px;}
        .table-search svg{width:15px;height:15px;color:var(--ink-soft);}
        .table-search input{border:none;background:transparent;outline:none;font-size:13.5px;width:100%;}
        .table-scroll{overflow-x:auto;}
        .table-pager{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 18px;border-top:1px solid var(--line);flex-wrap:wrap;}
        .pager-pages{display:flex;gap:6px;align-items:center;flex-wrap:wrap;}
        .pager-pages a,.pager-pages span.page{min-width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;padding:0 8px;border:1px solid var(--line);border-radius:8px;font-size:12.5px;font-weight:600;color:var(--coffee-700);text-decoration:none;background:var(--white);}
        .pager-pages a:hover{border-color:var(--gold-500);color:var(--terracotta-600);}
        .pager-pages span.active{background:var(--coffee-900);color:#fff;border-color:var(--coffee-900);}
        table{width:100%;border-collapse:collapse;min-width:680px;}
        thead th{
            text-align:left;font-size:11.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--ink-soft);
            padding:12px 18px;border-bottom:1px solid var(--line);background:var(--sand-50);font-weight:700;white-space:nowrap;
        }
        tbody td{padding:14px 18px;border-bottom:1px solid var(--line);font-size:13.5px;color:var(--coffee-900);}
        tbody tr:last-child td{border-bottom:none;}
        .table-pagination{padding:16px 18px;border-top:1px solid var(--line);display:flex;align-items:center;justify-content:center;gap:8px;}
        .table-pagination a,.table-pagination span{padding:8px 14px;border-radius:8px;font-size:12.5px;font-weight:600;border:1px solid var(--line);background:var(--white);color:var(--coffee-700);text-decoration:none;transition:all .2s;}
        .table-pagination a:hover{background:var(--sand-100);border-color:var(--coffee-300);}
        .table-pagination .active{background:var(--coffee-900);color:#fff;border-color:var(--coffee-900);}
        .table-pagination .disabled{opacity:.5;cursor:not-allowed;}
        tbody tr{transition:background .12s;}
        tbody tr:hover{background:var(--sand-50);}
        .cell-main{display:flex;align-items:center;gap:11px;}
        .thumb{width:42px;height:42px;border-radius:9px;object-fit:cover;flex:none;background:var(--sand-200);}
        .cell-title{font-weight:600;color:var(--coffee-900);}
        .cell-sub{font-size:12px;color:var(--ink-soft);margin-top:1px;}
        .tag{display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:20px;font-size:11.5px;font-weight:700;white-space:nowrap;}
        .tag-green{background:var(--acacia-100);color:var(--acacia-600);}
        .tag-gold{background:var(--gold-100);color:#8a6418;}
        .tag-red{background:var(--danger-100);color:var(--danger);}
        .tag-grey{background:var(--sand-200);color:var(--ink-soft);}
        .tag-terracotta{background:var(--terracotta-100);color:var(--terracotta-600);}
        .row-actions{display:flex;gap:6px;justify-content:flex-end;}
        .row-actions button{width:32px;height:32px;border-radius:8px;border:1px solid var(--line);background:var(--white);display:flex;align-items:center;justify-content:center;color:var(--coffee-700);}
        .row-actions button:hover{background:var(--sand-100);}
        .row-actions button svg{width:14.5px;height:14.5px;}
        .row-actions .danger:hover{background:var(--danger-100);color:var(--danger);border-color:var(--danger-100);}
        .row-actions .warn:hover{background:var(--gold-100);color:#8a6418;border-color:var(--gold-100);}
        .pwd-wrap{position:relative;}
        .pwd-wrap input{padding-right:42px;}
        .pwd-toggle{position:absolute;right:4px;top:50%;transform:translateY(-50%);width:32px;height:32px;border:none;background:transparent;cursor:pointer;font-size:14px;opacity:.7;border-radius:8px;}
        .pwd-toggle:hover{opacity:1;background:var(--sand-200);}
        .input-icon-wrap{position:relative;}
        .input-icon-wrap .input-icon{position:absolute;left:14px;top:0;bottom:0;margin:auto 0;width:16px;height:16px;color:var(--coffee-300);pointer-events:none;}
        .field .input-icon-wrap input,.field .input-icon-wrap textarea{padding-left:42px;}
        .settings-section{display:flex;align-items:center;gap:9px;margin:24px 0 4px;padding-top:8px;}
        .settings-section:first-of-type{margin-top:0;padding-top:0;}
        .settings-section::before{content:'';width:22px;height:3px;border-radius:2px;background:var(--gold-500);flex:none;}
        .settings-section h4{margin:0;font-size:12.5px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--coffee-700);}
        .empty-state{padding:60px 20px;text-align:center;color:var(--ink-soft);}
        .empty-state svg{width:46px;height:46px;color:var(--coffee-300);margin-bottom:12px;}
        .empty-state h4{margin-bottom:5px;color:var(--coffee-800);}
        .empty-state p{font-size:13.5px;}


        /* Buttons */
        .btn{
            display:inline-flex;align-items:center;justify-content:center;gap:8px;
            padding:12px 20px;border-radius:var(--radius-sm);border:none;
            font-weight:600;font-size:14.5px;transition:transform .12s, box-shadow .12s, background .15s;
        }
        .btn:active{transform:translateY(1px);}
        .btn-primary{background:var(--terracotta-600);color:#fff;box-shadow:0 6px 16px rgba(194,89,43,.32);}
        .btn-primary:hover{background:var(--terracotta-500);}
        .btn-ghost{background:transparent;color:var(--coffee-700);border:1.5px solid var(--line);}
        .btn-ghost:hover{background:var(--sand-100);}
        .btn-soft{background:var(--sand-100);color:var(--coffee-800);}
        .btn-soft:hover{background:var(--sand-200);}
        .btn-danger{background:var(--danger-100);color:var(--danger);}
        .btn-danger:hover{background:#efc6c2;}
        .btn-sm{padding:8px 13px;font-size:13px;}


        /* Gallery */
        .gallery-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:16px;}
        .gallery-card{position:relative;border-radius:var(--radius-md);overflow:hidden;aspect-ratio:1/1;box-shadow:var(--shadow-sm);border:1px solid var(--line);background:var(--sand-200);}
        .gallery-card img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .35s;}
        .gallery-card:hover img{transform:scale(1.06);}
        .gallery-overlay{
            position:absolute;inset:0;background:linear-gradient(180deg,rgba(36,20,8,0) 45%,rgba(36,20,8,.82));
            display:flex;flex-direction:column;justify-content:flex-end;padding:12px;opacity:0;transition:opacity .2s;
        }
        .gallery-card:hover .gallery-overlay{opacity:1;}
        .gallery-cap{color:#fff;font-size:12.5px;font-weight:600;margin-bottom:8px;line-height:1.3;}
        .gallery-actions{display:flex;gap:6px;}
        .gallery-actions button{flex:1;padding:6px;border-radius:7px;border:none;background:rgba(255,255,255,.18);color:#fff;backdrop-filter:blur(4px);font-size:11.5px;font-weight:600;display:flex;align-items:center;justify-content:center;gap:5px;}
        .gallery-actions button:hover{background:rgba(255,255,255,.3);}
        .gallery-badge{position:absolute;top:10px;left:10px;background:rgba(36,20,8,.65);color:#fff;font-size:10.5px;font-weight:700;padding:4px 9px;border-radius:20px;text-transform:uppercase;letter-spacing:.04em;backdrop-filter:blur(4px);}
        .add-tile{
            border:2px dashed var(--coffee-300);border-radius:var(--radius-md);aspect-ratio:1/1;
            display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;color:var(--coffee-500);
            background:var(--sand-100);font-size:12.5px;font-weight:600;cursor:pointer;
        }
        .add-tile:hover{background:var(--sand-200);border-color:var(--terracotta-500);color:var(--terracotta-600);}
        .add-tile svg{width:26px;height:26px;}


        /* Reviews */
        .review-card{display:flex;gap:14px;background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);padding:18px;box-shadow:var(--shadow-sm);margin-bottom:14px;}
        .review-avatar{width:44px;height:44px;border-radius:50%;flex:none;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:15px;background:linear-gradient(155deg,var(--terracotta-600),var(--gold-500));}
        .review-top{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:4px;flex-wrap:wrap;}
        .review-name{font-weight:700;color:var(--coffee-900);font-size:14.5px;}
        .review-tour{font-size:12px;color:var(--ink-soft);margin-top:1px;}
        .review-text{font-size:13.8px;color:var(--coffee-800);line-height:1.55;margin:8px 0 10px;}
        .review-actions{display:flex;gap:8px;}


        /* Messages */
        .msg-layout{display:grid;grid-template-columns:340px 1fr;gap:18px;align-items:start;}
        .msg-list{background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);overflow:hidden;box-shadow:var(--shadow-sm);}
        .msg-item{display:flex;gap:11px;padding:14px 16px;border-bottom:1px solid var(--line);cursor:pointer;position:relative;}
        .msg-item:hover{background:var(--sand-50);}
        .msg-item.active{background:var(--terracotta-100);}
        .msg-item.unread::before{content:"";position:absolute;left:6px;top:50%;transform:translateY(-50%);width:7px;height:7px;border-radius:50%;background:var(--terracotta-600);}
        .msg-item-name{font-weight:700;font-size:13.5px;color:var(--coffee-900);}
        .msg-item-prev{font-size:12px;color:var(--ink-soft);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:230px;}
        .msg-item-time{font-size:10.5px;color:var(--ink-soft);margin-left:auto;flex:none;}
        .msg-detail{background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);padding:24px;min-height:420px;display:flex;flex-direction:column;}
        .msg-detail-head{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1px solid var(--line);padding-bottom:16px;margin-bottom:16px;}
        .msg-detail-body{font-size:14.5px;line-height:1.7;color:var(--coffee-800);flex:1;}
        .msg-detail-foot{display:flex;gap:10px;margin-top:20px;padding-top:16px;border-top:1px solid var(--line);}


        /* Settings */
        .settings-grid{display:grid;grid-template-columns:240px 1fr;gap:24px;align-items:start;}
        .settings-grid-single{grid-template-columns:1fr;}
        .settings-nav{display:flex;flex-direction:column;gap:3px;background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);padding:10px;box-shadow:var(--shadow-sm);}
        .settings-nav button{
            display:flex;align-items:center;gap:10px;text-align:left;padding:11px 13px;border-radius:9px;border:none;background:transparent;
            font-size:13.8px;font-weight:600;color:var(--coffee-700);cursor:pointer;
        }
        .settings-nav button.active{background:var(--sand-100);color:var(--terracotta-600);}
        .settings-nav button svg{width:17px;height:17px;}
        .settings-panel{background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);padding:26px;}
        .settings-panel h3{font-size:17px;margin-bottom:18px;}
        .settings-pane{display:none;}
        .settings-pane.active{display:block;}
        .field{margin-bottom:16px;}
        .field label{display:block;font-size:12.5px;font-weight:600;color:var(--coffee-700);margin-bottom:7px;text-transform:uppercase;letter-spacing:.04em;}
        .field input,.field select,.field textarea{
            width:100%;padding:12px 14px;border:1.5px solid var(--line);border-radius:var(--radius-sm);
            background:var(--white);font-size:14.5px;color:var(--ink);transition:border-color .15s, box-shadow .15s;
        }
        .field input:focus,.field select:focus,.field textarea:focus{
            outline:none;border-color:var(--terracotta-500);box-shadow:0 0 0 3px var(--terracotta-100);
        }
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
        .toggle-row{display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid var(--line);}
        .toggle-row:last-child{border-bottom:none;}
        .toggle-text strong{display:block;font-size:14px;color:var(--coffee-900);margin-bottom:2px;}
        .toggle-text span{font-size:12.5px;color:var(--ink-soft);}
        .switch{width:42px;height:24px;border-radius:20px;background:var(--sand-200);position:relative;flex:none;border:none;cursor:pointer;transition:background .2s;}
        .switch::after{content:"";position:absolute;top:3px;left:3px;width:18px;height:18px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.25);transition:transform .2s;}
        .switch.on{background:var(--acacia-500);}
        .switch.on::after{transform:translateX(18px);}


        /* Modal */
        .modal-backdrop{
            position:fixed;inset:0;background:rgba(36,20,8,.5);backdrop-filter:blur(2px);
            display:none;align-items:flex-start;justify-content:center;z-index:400;padding:40px 20px;overflow-y:auto;
        }
        .modal-backdrop.show{display:flex;}
        .modal{
            background:var(--sand-50);border-radius:var(--radius-lg);width:100%;max-width:560px;
            box-shadow:var(--shadow-lg);animation:riseIn .3s cubic-bezier(.2,.8,.2,1);margin:auto;
        }
        @keyframes riseIn{from{opacity:0;transform:translateY(14px);}to{opacity:1;transform:translateY(0);}}
        .modal-head{display:flex;align-items:center;justify-content:space-between;padding:22px 24px;border-bottom:1px solid var(--line);}
        .modal-head h3{font-size:19px;}
        .modal-close{width:34px;height:34px;border-radius:9px;border:1px solid var(--line);background:var(--white);display:flex;align-items:center;justify-content:center;}
        .modal-body{padding:22px 24px;max-height:60vh;overflow-y:auto;}
        .modal-foot{display:flex;justify-content:flex-end;gap:10px;padding:18px 24px;border-top:1px solid var(--line);}


        /* Toast */
        #toastHost{position:fixed;bottom:24px;right:24px;z-index:600;display:flex;flex-direction:column;gap:10px;}
        .toast{
            background:var(--coffee-900);color:#fff;padding:13px 18px;border-radius:11px;font-size:13.5px;font-weight:600;
            box-shadow:var(--shadow-lg);display:flex;align-items:center;gap:10px;min-width:240px;animation:toastIn .3s ease;
        }
        .toast.success{background:var(--acacia-600);}
        .toast.error{background:var(--danger);}
        .toast svg{width:17px;height:17px;flex:none;}
        @keyframes toastIn{from{opacity:0;transform:translateX(20px);}to{opacity:1;transform:translateX(0);}}


        /* Responsive */
        .mobile-overlay{position:fixed;inset:0;background:rgba(36,20,8,.45);z-index:190;display:none;}
        .mobile-overlay.show{display:block;}
        @media (max-width:1180px){
            .stat-grid{grid-template-columns:repeat(2,1fr);}
            .panel-grid{grid-template-columns:1fr;}
            .settings-grid{grid-template-columns:1fr;}
            .msg-layout{grid-template-columns:1fr;}
        }
        @media (max-width:900px){
            .sidebar{transform:translateX(-100%);width:var(--sidebar-w);z-index:300;}
            .sidebar.mobile-open{transform:translateX(0);}
            .main{margin-left:0 !important;}
            .tb-search{display:none;}
        }
        @media (max-width:640px){
            .stat-grid{grid-template-columns:1fr;}
            .view-wrap{padding:16px;}
            .topbar{padding:0 14px;gap:10px;}
            .form-row{grid-template-columns:1fr;}
            .tb-live span{display:none;}
            .view-head h2{font-size:22px;}
        }
        @media (prefers-reduced-motion:reduce){
            *{animation-duration:.001ms !important;transition-duration:.001ms !important;}
        }
    </style>
</head>
<body>
    <div id="app" class="show">
        <div class="mobile-overlay" id="mobileOverlay" onclick="closeMobileSidebar()"></div>
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sb-brand">
                <img src="https://res.cloudinary.com/aenplcpl/image/upload/f_auto,q_auto,w_200/v1782890324/safari-logo-white_bexcal.png" alt="Tanzania Daily Tours & Safari" style="width: 38px; height: 38px; border-radius: 10px; flex: none;">
                <div class="sb-brand-text">
                    <strong>Tanzania Daily</strong>
                    <span>Tours & Safari · CMS</span>
                </div>
            </div>
            <nav class="sb-nav">
                <div class="sb-section-label">Overview</div>
                <a href="https://tanzaniadailytoursandsafari.com/live" class="sb-item ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="9" rx="1.5"></rect>
                        <rect x="14" y="3" width="7" height="5" rx="1.5"></rect>
                        <rect x="14" y="12" width="7" height="9" rx="1.5"></rect>
                        <rect x="3" y="16" width="7" height="5" rx="1.5"></rect>
                    </svg>
                    <span>Dashboard</span>
                </a>
                <div class="sb-section-label">Content</div>
                <a href="https://tanzaniadailytoursandsafari.com/live/destinations" class="sb-item ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 11 12 4l9 7"></path>
                        <path d="M5 10v10h14V10"></path>
                        <path d="M9 20v-6h6v6"></path>
                    </svg>
                    <span>Destinations</span>
                    <span class="badge" id="navDestCount">62</span>
                </a>
                <a href="https://tanzaniadailytoursandsafari.com/live/gallery" class="sb-item ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.8"></circle>
                        <path d="m21 15-5-5L5 21"></path>
                    </svg>
                    <span>Gallery</span>
                </a>
                <a href="https://tanzaniadailytoursandsafari.com/live/reviews" class="sb-item ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2.5 15.1 9 22 10 17 15 18.2 22 12 18.6 5.8 22 7 15 2 10 8.9 9"></polygon>
                    </svg>
                    <span>Reviews</span>
                    <span class="badge" id="navReviewCount">0</span>
                </a>
                <div class="sb-section-label">Operations</div>
                <a href="https://tanzaniadailytoursandsafari.com/live/bookings" class="sb-item ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="17" rx="2"></rect>
                        <path d="M16 2v4M8 2v4M3 9h18"></path>
                        <path d="m9 14 2 2 4-4"></path>
                    </svg>
                    <span>Bookings</span>
                    <span class="badge" id="navBookingCount">1</span>
                </a>
                <a href="https://tanzaniadailytoursandsafari.com/live/payments" class="sb-item ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                        <line x1="2" y1="10" x2="22" y2="10"></line>
                    </svg>
                    <span>Payments</span>
                    <span class="badge" id="navPaymentCount">5</span>
                </a>
                <a href="https://tanzaniadailytoursandsafari.com/live/messages" class="sb-item ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2Z"></path>
                    </svg>
                    <span>Messages</span>
                    <span class="badge" id="navMsgCount">0</span>
                </a>
                <div class="sb-section-label">System</div>
                <div class="sb-drop open">
                    <button type="button" class="sb-item sb-drop-toggle active" onclick="toggleSbDrop(this)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1.04 1.56V21a2 2 0 0 1-4 0v-.09A1.7 1.7 0 0 0 9 19.4a1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.56-1.04H3a2 2 0 0 1 0-4h.09A1.7 1.7 0 0 0 4.6 9a1.7 1.7 0 0 0-.34-1.87l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1.04-1.56V3a2 2 0 0 1 4 0v.09A1.7 1.7 0 0 0 15 4.6a1.7 1.7 0 0 0 1.87-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9a1.7 1.7 0 0 0 1.56 1.04H21a2 2 0 0 1 0 4h-.09A1.7 1.7 0 0 0 19.4 15Z"></path>
                        </svg>
                        <span>Site Settings</span>
                        <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <div class="sb-drop-menu">
                        <a href="https://tanzaniadailytoursandsafari.com/live/settings?pane=general" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                                <rect x="9" y="9" width="6" height="6"></rect>
                            </svg>
                            General
                        </a>
                        <a href="https://tanzaniadailytoursandsafari.com/live/settings?pane=brand" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="13.5" cy="6.5" r=".5"></circle>
                                <circle cx="17.5" cy="10.5" r=".5"></circle>
                                <circle cx="8.5" cy="7.5" r=".5"></circle>
                                <circle cx="6.5" cy="12.5" r=".5"></circle>
                                <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C22 6.012 17.461 2 12 2Z"></path>
                            </svg>
                            Brand & Colors
                        </a>
                        <a href="https://tanzaniadailytoursandsafari.com/live/settings?pane=contact" class="sb-drop-sub active">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92Z"></path>
                            </svg>
                            Contact & Social
                        </a>
                        <a href="https://tanzaniadailytoursandsafari.com/live/settings?pane=notifications" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path>
                                <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path>
                            </svg>
                            Notifications
                        </a>
                        <a href="https://tanzaniadailytoursandsafari.com/live/users" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            Admin Users
                        </a>
                        <a href="https://tanzaniadailytoursandsafari.com/live/settings?pane=payments" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                                <line x1="2" y1="10" x2="22" y2="10"></line>
                            </svg>
                            Payments
                        </a>
                        <a href="https://tanzaniadailytoursandsafari.com/live/settings?pane=mail" class="sb-drop-sub ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                            </svg>
                            Mail
                        </a>
                    </div>
                </div>
            </nav>
            <div class="sb-footer">
                <a href="https://tanzaniadailytoursandsafari.com/live/profile" class="sb-user" style="text-decoration:none;">
                    <div class="sb-avatar">
                        JE
                    </div>
                    <div class="sb-user-text">
                        <strong>Jeremia Developer</strong>
                        <span>jeremiat449@gmail.com</span>
                    </div>
                </a>
            </div>
        </aside>

        <!-- Main -->
        <div class="main" id="mainArea">
            <header class="topbar">
                <button class="tb-toggle" onclick="toggleSidebar()" aria-label="Toggle sidebar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="4" y1="7" x2="20" y2="7"></line>
                        <line x1="4" y1="12" x2="20" y2="12"></line>
                        <line x1="4" y1="17" x2="20" y2="17"></line>
                    </svg>
                </button>
                <div class="tb-search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" placeholder="Search tours, bookings, guests…">
                </div>
                <div class="tb-right">
                    <div class="tb-live"><span>Site live</span></div>
                    <a class="tb-iconbtn" href="https://tanzaniadailytoursandsafari.com" target="_blank" rel="noopener" aria-label="View live site" title="View live site">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                            <path d="M15 3h6v6"></path>
                            <path d="M10 14 21 3"></path>
                        </svg>
                    </a>
                    <form method="POST" action="https://tanzaniadailytoursandsafari.com/live/logout" style="display:inline;">
                        <input type="hidden" name="_token" value="w3Vlfvw2Bh8VfqolGpMryy5mbgT3npZg5cr409mV" autocomplete="off">                        <button type="submit" class="tb-iconbtn" aria-label="Log out" title="Log out">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16 17 21 12 16 7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                        </button>
                    </form>
                </div>
            </header>

            <div class="view-wrap">
                <!-- Session Messages -->
                
                <!-- Content -->
                <div class="view active">
    <div class="view-head">
        <div>
            <h2>Site Settings</h2>
            <p class="sub">Update business details, brand colors, and integrations.</p>
        </div>
        <div class="view-actions">
            <button type="submit" form="settingsForm" class="btn btn-primary">Save changes</button>
        </div>
    </div>

    <div class="settings-grid settings-grid-single">
        <div class="settings-panel">
            <form id="settingsForm" action="https://tanzaniadailytoursandsafari.com/live/settings" method="POST">
                <input type="hidden" name="_token" value="w3Vlfvw2Bh8VfqolGpMryy5mbgT3npZg5cr409mV" autocomplete="off">                <input type="hidden" name="_method" value="PUT">
                <div class="settings-pane active" id="pane-general">
                    <h3 style="font-size:17px;margin-bottom:18px;">General information</h3>
                                            <div class="field">
                            <label>Site Title</label>
                                                            <input type="text" name="content[site_title]" value="TDTS - Tanzania Wild">
                                                    </div>
                                            <div class="field">
                            <label>Site Tagline</label>
                                                            <input type="text" name="content[site_tagline]" value="Discover Tanzania&#039;s Wilderness">
                                                    </div>
                                            <div class="field">
                            <label>Default Currency</label>
                                                            <select name="content[default_currency]">
                                                                            <option selected>USD ($)</option>
                                        <option >TZS (TSh)</option>
                                        <option >EUR (€)</option>
                                                                    </select>
                                                    </div>
                                            <div class="field">
                            <label>Timezone</label>
                                                            <select name="content[timezone]">
                                                                            <option selected>Africa/Dar es Salaam (EAT)</option>
                                        <option >UTC</option>
                                                                    </select>
                                                    </div>
                                    </div>

                <div class="settings-pane" id="pane-brand" style="display:none;">
                    <h3 style="font-size:17px;margin-bottom:6px;">Brand & Assets</h3>
                    <p class="field-hint" style="margin-bottom:16px;">These mirror the live site's safari palette — earthy tones with espresso, orange and acacia-green accents.</p>
                    <div class="color-swatch-row">
                        <div class="color-swatch">
                            <div class="swatch" style="background: #631e08;"></div>
                            <span class="swatch-label">Espresso</span>
                        </div>
                        <div class="color-swatch">
                            <div class="swatch" style="background: #ff9729;"></div>
                            <span class="swatch-label">Orange</span>
                        </div>
                        <div class="color-swatch">
                            <div class="swatch" style="background: #088529;"></div>
                            <span class="swatch-label">Acacia Green</span>
                        </div>
                        <div class="color-swatch">
                            <div class="swatch" style="background: #854208;"></div>
                            <span class="swatch-label">Brown</span>
                        </div>
                        <div class="color-swatch">
                            <div class="swatch" style="background: #f8f4f0;"></div>
                            <span class="swatch-label">Sand</span>
                        </div>
                    </div>
                                            <div class="field" style="margin-top:22px;">
                            <label>Logo (Brown)</label>
                                                            <div class="space-y-2">
                                                                            <img src="https://res.cloudinary.com/aenplcpl/image/upload/v1782890324/safari-logo-brown_d1vgxe.png" alt="Logo (Brown)" style="width: 64px; height: 64px; object-fit: cover; border-radius: 8px; border: 1px solid var(--line);">
                                                                        <input type="text" name="content[logo_brown]" value="https://res.cloudinary.com/aenplcpl/image/upload/v1782890324/safari-logo-brown_d1vgxe.png" placeholder="Image URL">
                                </div>
                                                    </div>
                                            <div class="field" style="margin-top:22px;">
                            <label>Logo (White)</label>
                                                            <div class="space-y-2">
                                                                            <img src="https://res.cloudinary.com/aenplcpl/image/upload/v1782890324/safari-logo-white_bexcal.png" alt="Logo (White)" style="width: 64px; height: 64px; object-fit: cover; border-radius: 8px; border: 1px solid var(--line);">
                                                                        <input type="text" name="content[logo_white]" value="https://res.cloudinary.com/aenplcpl/image/upload/v1782890324/safari-logo-white_bexcal.png" placeholder="Image URL">
                                </div>
                                                    </div>
                                    </div>

                <div class="settings-pane" id="pane-contact" style="display:none;">
                    <h3 style="font-size:17px;margin-bottom:6px;">Contact & Social</h3>
                    <p style="font-size:12.5px;color:var(--ink-soft);margin-bottom:6px;">Used across the site header, footer, contact page and booking confirmations.</p>

                    
                    <div class="settings-section"><h4>Contact details</h4></div>
                    <div class="form-row">
                        <div class="field">
                            <label>Phone Number</label>
                            <div class="input-icon-wrap">
                                <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92Z"></path>
                                </svg>
                                <input type="tel" name="content[contact_phone]" value="+255 623 975 934" placeholder="+255 ...">
                            </div>
                        </div>
                        <div class="field">
                            <label>WhatsApp Number</label>
                            <div class="input-icon-wrap">
                                <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 11.5a8.38 8.38 0 0 1-8.5 8.5 8.5 8.5 0 0 1-3.8-.9L3 21l1.9-5.7A8.38 8.38 0 0 1 4 11.5 8.5 8.5 0 0 1 12.5 3a8.38 8.38 0 0 1 8.5 8.5Z"></path>
                                    <path d="M8.5 9.5c0 4.5 2.5 7 7 7"></path>
                                </svg>
                                <input type="tel" name="content[contact_whatsapp]" value="+255 623 975 934" placeholder="+255 ...">
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="field">
                            <label>Email Address</label>
                            <div class="input-icon-wrap">
                                <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                                    <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                                </svg>
                                <input type="email" name="content[contact_email]" value="info.tanzaniadailytours@gmail.com" placeholder="info@example.com">
                            </div>
                        </div>
                        <div class="field">
                            <label>Location</label>
                            <div class="input-icon-wrap">
                                <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                                <input type="text" name="content[contact_location]" value="Wakala wa Vipimo - Moshi - Kilimanjaro" placeholder="City, Country">
                            </div>
                        </div>
                    </div>

                    <div class="settings-section"><h4>Social media</h4></div>
                    <div class="field">
                        <label>Instagram URL</label>
                        <div class="input-icon-wrap">
                            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="2" width="20" height="20" rx="5"></rect>
                                <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37Z"></path>
                                <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                            </svg>
                            <input type="url" name="content[social_instagram]" value="https://instagram.com/tanzania_dailytours_and_safari" placeholder="https://instagram.com/yourpage">
                        </div>
                        <p class="field-hint">Shown in the footer and contact page.</p>
                    </div>

                    <div class="settings-section"><h4>Contact page content</h4></div>
                    <div style="font-size:12.5px;color:var(--ink-soft);margin-bottom:14px;">Headline, subtitle and description displayed on the public contact page.</div>
                    <div class="form-row">
                        <div class="field">
                            <label>Contact Page Title</label>
                            <input type="text" name="content[contact_page_title]" value="Get in Touch">
                        </div>
                        <div class="field">
                            <label>Contact Subtitle</label>
                            <input type="text" name="content[contact_subtitle]" value="Plan Your Perfect Safari">
                        </div>
                    </div>
                    <div class="field">
                        <label>Contact Description</label>
                        <textarea name="content[contact_description]" rows="4" placeholder="A short description shown on the contact page">Ready to start your adventure? Send us a message and we&#039;ll get back to you within 24 hours to help plan your personalized Tanzanian experience.</textarea>
                    </div>
                </div>

                <div class="settings-pane" id="pane-notifications" style="display:none;">
                    <h3 style="font-size:17px;margin-bottom:6px;">Notification preferences</h3>
                                            <div class="toggle-row">
                            <div class="toggle-text">
                                <strong>New Booking Alerts</strong>
                                <span>0</span>
                            </div>
                            <input type="hidden" name="content[notify_new_booking]" value="0">
                            <button type="button" class="switch " onclick="this.classList.toggle('on'); this.previousElementSibling.value = this.classList.contains('on') ? '1' : '0'"></button>
                        </div>
                                            <div class="toggle-row">
                            <div class="toggle-text">
                                <strong>New Message Alerts</strong>
                                <span>0</span>
                            </div>
                            <input type="hidden" name="content[notify_new_message]" value="0">
                            <button type="button" class="switch " onclick="this.classList.toggle('on'); this.previousElementSibling.value = this.classList.contains('on') ? '1' : '0'"></button>
                        </div>
                                            <div class="toggle-row">
                            <div class="toggle-text">
                                <strong>Review Moderation</strong>
                                <span>0</span>
                            </div>
                            <input type="hidden" name="content[review_moderation]" value="0">
                            <button type="button" class="switch " onclick="this.classList.toggle('on'); this.previousElementSibling.value = this.classList.contains('on') ? '1' : '0'"></button>
                        </div>
                                            <div class="toggle-row">
                            <div class="toggle-text">
                                <strong>Weekly Summary</strong>
                                <span>0</span>
                            </div>
                            <input type="hidden" name="content[weekly_summary]" value="0">
                            <button type="button" class="switch " onclick="this.classList.toggle('on'); this.previousElementSibling.value = this.classList.contains('on') ? '1' : '0'"></button>
                        </div>
                                    </div>
            </form>

            <div style="border-top:1px solid var(--line);margin:26px -26px 0;"></div>

            <form id="paymentSettingsForm" action="https://tanzaniadailytoursandsafari.com/live/payments/settings" method="POST">
                <input type="hidden" name="_token" value="w3Vlfvw2Bh8VfqolGpMryy5mbgT3npZg5cr409mV" autocomplete="off">                <input type="hidden" name="_method" value="PUT">                <div class="settings-pane" id="pane-payments" style="display:none;padding-top:26px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;gap:12px;flex-wrap:wrap;">
                        <div>
                            <h3 style="font-size:17px;">Payment Integration</h3>
                            <p style="font-size:12.5px;color:var(--ink-soft);margin-top:3px;">PesaPal credentials, environment and charging rules. Credentials are stored encrypted.</p>
                        </div>
                        <a href="https://tanzaniadailytoursandsafari.com/live/payments/settings" style="font-size:12.5px;font-weight:700;color:var(--terracotta-600);">Manage full payment settings →</a>
                    </div>

                    <div class="toggle-row">
                        <div class="toggle-text">
                            <strong>Online payments enabled</strong>
                            <span>Redirect customers to PesaPal checkout after booking</span>
                        </div>
                        <input type="hidden" name="payment_enabled" value="0">
                        <button type="button" class="switch on" onclick="this.classList.toggle('on'); this.previousElementSibling.value = this.classList.contains('on') ? '1' : '0'"></button>
                    </div>

                    <div class="field" style="margin-top:18px;">
                        <label>Environment</label>
                        <select name="pesapal_environment">
                            <option value="sandbox" >Sandbox (test)</option>
                            <option value="live" selected>Live (production)</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="field">
                            <label>Consumer key</label>
                            <input type="password" name="pesapal_consumer_key" placeholder="•••••••• (saved — leave blank to keep)" autocomplete="off">
                        </div>
                        <div class="field">
                            <label>Consumer secret</label>
                            <input type="password" name="pesapal_consumer_secret" placeholder="•••••••• (saved — leave blank to keep)" autocomplete="off">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="field">
                            <label>Charging currency</label>
                            <select name="pesapal_currency">
                                                                    <option value="USD" selected>USD</option>
                                                                    <option value="EUR" >EUR</option>
                                                                    <option value="GBP" >GBP</option>
                                                                    <option value="CAD" >CAD</option>
                                                                    <option value="AUD" >AUD</option>
                                                                    <option value="TZS" >TZS</option>
                                                                    <option value="KES" >KES</option>
                                                                    <option value="UGX" >UGX</option>
                                                                    <option value="ZAR" >ZAR</option>
                                                            </select>
                        </div>
                        <div class="field">
                            <label>Deposit percentage (0 = full amount)</label>
                            <input type="number" name="pesapal_deposit_percentage" min="0" max="100" value="30" required>
                        </div>
                    </div>

                    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:6px;">
                        <button type="submit" class="btn btn-primary btn-sm">Save payment settings</button>
                        <button type="button" onclick="submitPaymentAction('https://tanzaniadailytoursandsafari.com/live/payments/settings/test')" class="btn btn-soft btn-sm">Test connection</button>
                        <button type="button" onclick="submitPaymentAction('https://tanzaniadailytoursandsafari.com/live/payments/settings/ipn')" class="btn btn-soft btn-sm">Register IPN URL</button>
                    </div>
                </div>
            </form>

            <div style="border-top:1px solid var(--line);margin:26px -26px 0;"></div>

            <form id="mailSettingsForm" action="https://tanzaniadailytoursandsafari.com/live/settings/mail" method="POST">
                <input type="hidden" name="_token" value="w3Vlfvw2Bh8VfqolGpMryy5mbgT3npZg5cr409mV" autocomplete="off">                <input type="hidden" name="_method" value="PUT">                <div class="settings-pane" id="pane-mail" style="display:none;padding-top:26px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;gap:12px;flex-wrap:wrap;">
                        <div>
                            <h3 style="font-size:17px;">Mail Settings</h3>
                            <p style="font-size:12.5px;color:var(--ink-soft);margin-top:3px;">SMTP credentials used to send booking confirmations, payment and notification emails.</p>
                        </div>
                                                    <span class="tag tag-green">SMTP password saved</span>
                                            </div>

                    <div class="form-row">
                        <div class="field" style="flex:1.6;">
                            <label>SMTP host</label>
                            <input type="text" name="mail_smtp_host" value="smtp.gmail.com" placeholder="smtp.gmail.com" required>
                        </div>
                        <div class="field" style="flex:0.7;">
                            <label>Port</label>
                            <input type="number" name="mail_smtp_port" value="587" min="1" max="65535" required>
                        </div>
                        <div class="field" style="flex:0.8;">
                            <label>Encryption</label>
                            <select name="mail_smtp_encryption">
                                <option value="tls" selected>TLS</option>
                                <option value="ssl" >SSL</option>
                                <option value="none" >None</option>
                            </select>
                        </div>
                    </div>

                    <div class="field">
                        <label>SMTP username</label>
                        <input type="text" name="mail_smtp_username" value="info.tanzaniadailytours@gmail.com" placeholder="you@example.com" required>
                    </div>

                    <div class="field">
                        <label>App password</label>
                        <input type="password" name="mail_smtp_password" autocomplete="new-password" placeholder="•••••••• (saved — leave blank to keep)">
                        <p class="field-hint">For Gmail, generate an App Password (not your normal sign-in password). Stored encrypted.</p>
                    </div>

                    <div class="form-row">
                        <div class="field">
                            <label>From name</label>
                            <input type="text" name="mail_from_name" value="Tanzania Daily Tours &amp; Safari" required>
                        </div>
                        <div class="field">
                            <label>From email</label>
                            <input type="email" name="mail_from_address" value="info.tanzaniadailytours@gmail.com" required>
                        </div>
                    </div>

<div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:6px;">
                        <button type="submit" class="btn btn-primary btn-sm">Save mail settings</button>
                    </div>
                </div>
            </form>

            <form action="https://tanzaniadailytoursandsafari.com/live/settings/mail/test" method="POST">
                <input type="hidden" name="_token" value="w3Vlfvw2Bh8VfqolGpMryy5mbgT3npZg5cr409mV" autocomplete="off">                <div class="settings-pane" id="pane-mail-test" style="display:none;padding-top:24px;">
                    <div style="border-top:1px solid var(--line);margin:0 -26px;padding:20px 26px 0;"></div>
                    <h3 style="font-size:15px;margin-bottom:10px;">Send test email</h3>
                    <p style="font-size:12.5px;color:var(--ink-soft);margin-bottom:14px;">Send a test email using the saved settings to confirm everything works.</p>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
                        <div class="field" style="margin-bottom:0;">
                            <label>Send to</label>
                            <input type="email" name="to" value="" placeholder="recipient@example.com">
                        </div>
                        <button type="submit" class="btn btn-soft btn-sm">Send test email</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const SETTINGS_PANES = ['general', 'brand', 'contact', 'notifications', 'payments', 'mail'];

function setSettingsPane(pane) {
    document.querySelectorAll('.settings-pane').forEach(p => p.style.display = 'none');
    const mailPanes = ['mail', 'mail-test'];
    if (mailPanes.includes(pane)) {
        mailPanes.forEach(id => {
            const el = document.getElementById('pane-' + id);
            if (el) el.style.display = 'block';
        });
        return;
    }
    document.getElementById('pane-' + pane).style.display = 'block';
}

(function initPaneFromQuery() {
    const pane = new URLSearchParams(location.search).get('pane');
    if (pane && SETTINGS_PANES.includes(pane)) {
        setSettingsPane(pane);
    }
})();

function submitPaymentAction(url) {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    form.innerHTML = '<input type="hidden" name="_token" value="' + csrf + '">';
    document.body.appendChild(form);
    form.submit();
}
</script>

            </div>
        </div>
    </div>
    <div id="toastHost"></div>
    <script>
        // Auto-logout timer (5 minutes in milliseconds)
        const AUTO_LOGOUT_TIME = 5 * 60 * 1000;
        let autoLogoutTimer;

        // Reset the auto-logout timer on user activity
        function resetAutoLogoutTimer() {
            clearTimeout(autoLogoutTimer);
            autoLogoutTimer = setTimeout(() => {
                // Clear the session on the server side by redirecting to login
                window.location.href = "https://tanzaniadailytoursandsafari.com/live/login";
            }, AUTO_LOGOUT_TIME);
        }

        // Add event listeners for user activity
        ['click', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
            document.addEventListener(event, resetAutoLogoutTimer, true);
        });

        // Initialize the timer when the page loads
        resetAutoLogoutTimer();

        function toggleSidebar(){
            if(window.innerWidth <= 900){
                document.getElementById('sidebar').classList.toggle('mobile-open');
                document.getElementById('mobileOverlay').classList.toggle('show');
            } else {
                document.getElementById('sidebar').classList.toggle('collapsed');
            }
        }
        function closeMobileSidebar(){
            document.getElementById('sidebar').classList.remove('mobile-open');
            document.getElementById('mobileOverlay').classList.remove('show');
        }
        function toggleSbDrop(el){
            const drop = el.closest('.sb-drop');
            const wasOpen = drop.classList.contains('open');
            document.querySelectorAll('.sb-drop').forEach(d => d.classList.remove('open'));
            if(!wasOpen) drop.classList.add('open');
        }

        function toast(msg, type='default'){
            const host = document.getElementById('toastHost');
            const el = document.createElement('div');
            el.className = 'toast ' + (type==='success'?'success':type==='error'?'error':'');
            el.innerHTML = (type==='success' ? '<svg viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><path d=\"M20 6 9 17l-5-5\"></path></svg>' : '') + '<span>'+msg+'</span>';
            host.appendChild(el);
            setTimeout(()=>{ el.style.opacity='0'; el.style.transform='translateX(20px)'; el.style.transition='all .25s'; setTimeout(()=>el.remove(),250); }, 3000);
        }

        function openModal(id){ document.getElementById(id).classList.add('show'); }
        function closeModal(id){ document.getElementById(id).classList.remove('show'); }
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Safari Admin · Tanzania Daily Tours & Safari</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;0,9..144,700;1,9..144,500&family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root{
            --sand-50:#FBF7EF;
            --sand-100:#F4ECDC;
            --sand-200:#E9DCC0;
            --coffee-900:#2A1B10;
            --coffee-800:#3B2718;
            --coffee-700:#4D3422;
            --coffee-500:#7A5C42;
            --coffee-300:#A98968;
            --terracotta-600:#C2592B;
            --terracotta-500:#D06B3A;
            --terracotta-100:#F6E1D3;
            --acacia-600:#5E6E3F;
            --acacia-500:#7A8450;
            --acacia-100:#E2E7D4;
            --gold-500:#D4A24C;
            --gold-100:#F7E9CB;
            --ink:#241408;
            --ink-soft:#6B5A48;
            --line:#E4D7C2;
            --white:#FFFFFF;
            --danger:#B33A3A;
            --danger-100:#F6DCDA;
            --success:#3F6B3F;
            --radius-sm:8px;
            --radius-md:14px;
            --radius-lg:20px;
            --shadow-sm:0 1px 2px rgba(42,27,16,.08);
            --shadow-md:0 8px 24px rgba(42,27,16,.10);
            --shadow-lg:0 20px 48px rgba(42,27,16,.18);
        }

        *{box-sizing:border-box;}
        html,body{height:100%;}
        body{
            margin:0;
            font-family:'Raleway',sans-serif;
            background:
                radial-gradient(circle at 15% 20%, rgba(212,162,76,.18), transparent 45%),
                radial-gradient(circle at 85% 80%, rgba(122,132,80,.18), transparent 45%),
                var(--coffee-900);
            color:var(--ink);
            -webkit-font-smoothing:antialiased;
            overflow-x:hidden;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:24px;
        }
        body::before{
            content:"";
            position:absolute;
            inset:0;
            background-image: repeating-linear-gradient(115deg, rgba(255,255,255,.025) 0 2px, transparent 2px 64px);
            pointer-events:none;
        }
        .login-card{
            position:relative;
            width:100%;
            max-width:392px;
            background:var(--sand-50);
            border-radius:var(--radius-lg);
            padding:40px 36px 32px;
            box-shadow:var(--shadow-lg);
            animation:riseIn .5s cubic-bezier(.2,.8,.2,1);
        }
        @keyframes riseIn{
            from{opacity:0;transform:translateY(14px);}
            to{opacity:1;transform:translateY(0);}
        }
        .login-mark{
            width:72px;height:72px;border-radius:14px;
            background:linear-gradient(155deg,var(--terracotta-600),var(--gold-500));
            display:flex;align-items:center;justify-content:center;
            margin-bottom:18px;box-shadow:var(--shadow-sm);
            margin-left: auto;
            margin-right: auto;
        }
        .login-mark svg{width:28px;height:28px;}
        .login-card h1{
            font-family:'Raleway',sans-serif;
            font-size:26px;margin-bottom:6px;color:var(--coffee-900);
        }
        .login-sub{
            color:var(--ink-soft);font-size:14px;margin-bottom:26px;line-height:1.5;
        }
        .field{margin-bottom:16px;}
        .field label{
            display:block;font-size:12.5px;font-weight:600;color:var(--coffee-700);margin-bottom:7px;text-transform:uppercase;letter-spacing:.04em;
        }
        .field input{
            width:100%;padding:12px 14px;border:1.5px solid var(--line);border-radius:var(--radius-sm);
            background:var(--white);font-size:14.5px;color:var(--ink);transition:border-color .15s, box-shadow .15s;
        }
        .field input:focus{
            outline:none;border-color:var(--terracotta-500);box-shadow:0 0 0 3px var(--terracotta-100);
        }
        .btn{
            display:inline-flex;align-items:center;justify-content:center;gap:8px;
            padding:12px 20px;border-radius:var(--radius-sm);border:none;
            font-weight:600;font-size:14.5px;transition:transform .12s, box-shadow .12s, background .15s;
            cursor:pointer;
        }
        .btn:active{transform:translateY(1px);}
        .btn-primary{background:var(--terracotta-600);color:#fff;box-shadow:0 6px 16px rgba(194,89,43,.32);}
        .btn-primary:hover{background:var(--terracotta-500);}
        .btn-block{width:100%;}
        .login-error{
            display:none;background:var(--danger-100);color:var(--danger);font-size:13px;font-weight:600;
            padding:10px 12px;border-radius:var(--radius-sm);margin-bottom:14px;
        }
        .login-demo{
            margin-top:18px;padding:12px 14px;background:var(--sand-100);border:1px dashed var(--coffee-300);
            border-radius:var(--radius-sm);font-size:12.5px;color:var(--ink-soft);line-height:1.6;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-mark">
            <img src="https://res.cloudinary.com/aenplcpl/image/upload/f_auto,q_auto,w_200/v1782890324/safari-logo-white_bexcal.png" alt="Tanzania Daily Tours & Safari" style="width: 60px; height: 60px; object-fit: contain;">
        </div>
        <h1 style="text-align: center;">Tanzania Daily Tours & Safari</h1>
        <h2 style="font-family: 'Raleway', sans-serif; font-size: 20px; margin-bottom: 8px; color: var(--terracotta-600); text-align: center;">Admin Control Panel</h2>
        <p class="login-sub" style="text-align: center;">Managing Tanzania Daily Tours & Safari</p>

                    <div class="login-error" style="display:block;">
                Session expired! Please login again.
            </div>
        
        <form method="POST" action="https://tanzaniadailytoursandsafari.com/live/login">
            <input type="hidden" name="_token" value="w3Vlfvw2Bh8VfqolGpMryy5mbgT3npZg5cr409mV" autocomplete="off">            <div class="field">
                <label>Email address</label>
                <input type="email" name="email" placeholder="you@tanzaniadailytours.com" value="@gmail.com" required>
            </div>
            <div class="field">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>

        <div style="text-align: center; margin-top: 20px;">
            <a href="https://tanzaniadailytoursandsafari.com" style="color: var(--terracotta-600); text-decoration: none; font-size: 14px; display: inline-flex; align-items: center; gap: 6px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m12 19-7-7 7-7"></path>
                    <path d="M19 12H5"></path>
                </svg>
                Back to Website
            </a>
        </div>

         </div>
</body>
</html>
