Unicode true

!include "MUI2.nsh"
!include "FileFunc.nsh"
!include "WinMessages.nsh"

; Sürüm bilgisi.
; Build sirasinda version.txt dosyasindan okunur, installer .exe adina ve
; Windows "Programlar ve Ozellikler" listesine eklenecek DisplayVersion
; degerine yansitilir. Yeni bir surum cikartirken yalnizca files/version.txt'yi
; guncellemek yeterli — burayi elle degistirmenize gerek yok.
;
; !searchparse compile-time'da dosyadan regex ile deger cikarir. version.txt
; icinde duz metin olarak "0.5" yazmasi yeterli (yeni satir karakteri olsa
; bile sorunsuz okunur).
!searchparse /file "files\version.txt" "" APP_VERSION ""

; Build tarihi.
; Her .exe build edisinde elle guncellenir. yyyy-mm-dd formatinda yaz.
; Sadece install sirasinda DB yedegi alinirken yedek dosya adina yazilir
; (orn. at_install_backup_2026-05-02.sql). Tek kullanicili bir uygulama
; oldugu icin runtime tarihi yerine compile-time tarihi yeterlidir;
; build ile install genelde ayni gun yapilir.
!define BUILD_DATE "2026-05-02"

; Kurulum tanımlamaları
Name "Anime Tracker"
OutFile "AnimeTracker-v${APP_VERSION}.exe"
 
InstallDir "$PROGRAMFILES\AnimeTracker"
RequestExecutionLevel admin

; .exe dosyasinin Explorer/taskbar'da gozuken ikonu.
; Bu .ico dosyasi installer.nsi ile ayni klasorde olmali.
; Multi-resolution ICO (16/32/48/64/128/256 boyutlari icerir).
Icon "anime-tracker-icon.ico"
UninstallIcon "anime-tracker-icon.ico"

; XAMPP installer dosya adi
; Bu dosya installer.nsi ile ayni klasorde olmali (compile sirasinda embed edilir).
; XAMPP guncellenince bu satiri ve dosyayi degistir.
!define XAMPP_INSTALLER "xampp-windows-x64-8.2.12-0-VS16-installer.exe"

; Modern UI tanımlamaları
!define MUI_ABORTWARNING
!define MUI_ICON "anime-tracker-icon.ico"
!define MUI_UNICON "anime-tracker-icon.ico"

; Kurulum mesajları
!define MUI_WELCOMEPAGE_TITLE "Anime Tracker Kurulum Sihirbazı"
!define MUI_WELCOMEPAGE_TEXT "Bu sihirbaz size Anime Tracker uygulamasının kurulumunda rehberlik edecektir.$\r$\n$\r$\nKuruluma başlamadan önce lütfen diğer uygulamaları kapatınız.$\r$\n$\r$\nKurulum sırasında önce kurulu değilse XAMPP programı kurulacak ardından MySQL ve Apache servisleri başlatıldıktan sonra Anime Tracker web sitesi içerikleri oluşturulacaktır.$\r$\n$\r$\nDevam etmek için İleri'ye tıklayın."

; Özel bitiş sayfası metni
!define MUI_FINISHPAGE_TITLE "Kurulum Tamamlandı"
!define MUI_FINISHPAGE_TEXT "Anime Tracker başarıyla kuruldu.$\r$\n$\r$\nUygulamayı başlatmak için Bitir'e tıklayın."

; Özel link ve buton tanımlamaları
!define MUI_FINISHPAGE_RUN
!define MUI_FINISHPAGE_RUN_TEXT "Anime Tracker'ı Başlat"
!define MUI_FINISHPAGE_RUN_FUNCTION "LaunchApplication"
!define MUI_FINISHPAGE_LINK "Geliştirici sitesi"
!define MUI_FINISHPAGE_LINK_LOCATION "https://www.sicakcikolata.com"

; Sayfalar
!insertmacro MUI_PAGE_WELCOME
!insertmacro MUI_PAGE_COMPONENTS
!insertmacro MUI_PAGE_DIRECTORY
!insertmacro MUI_PAGE_INSTFILES
!insertmacro MUI_PAGE_FINISH

; Kaldırma sayfaları
!insertmacro MUI_UNPAGE_CONFIRM
!insertmacro MUI_UNPAGE_INSTFILES

; Dil
!insertmacro MUI_LANGUAGE "Turkish"

; Bileşen tanımlamaları
Section "Ana Bileşenler" SEC01
    SetOutPath "$INSTDIR"
    SetOverwrite on
    
    ; Logo dosyasini program dizinine kopyala. Hem Windows uninstall
    ; listesinde DisplayIcon olarak hem de masaustu kisayolunun ikonu
    ; olarak kullanilacak. anime-tracker-icon.ico installer.nsi ile
    ; ayni klasorde olmali.
    File "anime-tracker-icon.ico"
    
    WriteUninstaller "$INSTDIR\uninstall.exe"
    
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\AnimeTracker" \
    "DisplayName" "Anime Tracker"
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\AnimeTracker" \
    "DisplayVersion" "${APP_VERSION}"
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\AnimeTracker" \
    "Publisher" "Okan Sumer"
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\AnimeTracker" \
    "URLInfoAbout" "https://www.sicakcikolata.com"
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\AnimeTracker" \
    "UninstallString" "$INSTDIR\uninstall.exe"
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\AnimeTracker" \
    "DisplayIcon" "$INSTDIR\anime-tracker-icon.ico"
SectionEnd

Section "XAMPP Kurulumu" SecXAMPP
    ; XAMPP zaten kurulu mu kontrol et
    ; Eger kuruluysa indirme/kurma adimlari atlanir, ama servis kayit ve
    ; baslatma her durumda calismalidir cunku kullanicinin XAMPP'i Windows
    ; servisi olarak register edilmemis veya servisler durmus olabilir.
    IfFileExists "C:\xampp\xampp-control.exe" XamppAlreadyInstalled 0
    
    SetOutPath "$TEMP"
    DetailPrint "XAMPP cikartiliyor..."
    
    ; XAMPP installer'i embed edilmis dosyadan cikar.
    ; NSISdl::download kullanmiyoruz cunku HTTPS desteklemiyordu ve
    ; SourceForge'a baglanamiyordu. Bunun yerine XAMPP installer'i
    ; AnimeTracker.exe icine gomulu — internet bagliligi gerekmez.
    File "${XAMPP_INSTALLER}"
    
    DetailPrint "XAMPP kuruluyor..."
    ExecWait '"$TEMP\${XAMPP_INSTALLER}" --mode unattended'
    Delete "$TEMP\${XAMPP_INSTALLER}"
    Goto XamppSetupDone
    
    XamppAlreadyInstalled:
    DetailPrint "XAMPP zaten kurulu, indirme atlandi."
    
    XamppSetupDone:
    
    DetailPrint "Apache ve MySQL Windows servisi olarak kayit ediliyor..."
    
    ; Apache'yi Windows servisi olarak kur (idempotent — yeniden calistirmak guvenli)
    ExecWait '"C:\xampp\apache\bin\httpd.exe" -k install -n "Apache2.4"'
    
    ; MySQL'i Windows servisi olarak kur (zaten kuruluysa hata sessiz gecer)
    ExecWait '"C:\xampp\mysql\bin\mysqld.exe" --install MySQL'
    
    DetailPrint "XAMPP servisleri baslatiliyor..."
    ExecWait 'net start "Apache2.4"'
    ExecWait 'net start "MySQL"'
    
    ; mysqld baslatildiktan sonra gercek baglanti kabul edebilmesi icin
    ; kisa bir bekleme. SecMain'deki SQL komutlari bagli olarak hazir bekler.
    Sleep 2000
SectionEnd

Section "Anime Tracker Kurulumu" SecMain
    SetOutPath "C:\xampp\htdocs\anime_tracker"
    
    File /r "files\*.*"
    
    CreateDirectory "$OUTDIR\img"
    CreateDirectory "$OUTDIR\uploads"
    CreateDirectory "$OUTDIR\docs"
    
    ; Veritabanı varlığını kontrol et
    nsExec::ExecToStack '"C:\xampp\mysql\bin\mysql.exe" -u root -e "SHOW DATABASES LIKE \"anime_tracker\""'
    Pop $0 ; Çıkış kodu
    Pop $1 ; Komut çıktısı
    
    ${If} $1 == ""
        ; Veritabanı yoksa yeni oluştur
        DetailPrint "Veritabanı bulunamadı. Yeni veritabanı oluşturuluyor..."
        ExecWait '"C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS anime_tracker"'
        
        ; Sema dosyasini kullanarak tablolari olustur
        ; cmd.exe ile wrap edilir cunku ExecWait dogrudan bir shell baslatmaz
        ; ve < redirect operatorunu yorumlayamaz.
        ExecWait '"$SYSDIR\cmd.exe" /C ""C:\xampp\mysql\bin\mysql.exe" -u root anime_tracker < "$OUTDIR\schema.sql""'
        DetailPrint "Veritabanı başarıyla oluşturuldu."
    ${Else}
        ; Veritabani zaten var. Dosyalar zaten "files\*.*" ile uzerine yazildi
        ; (yukarida, satir 142'deki File /r komutu). Yeni PHP dosyalari eski
        ; schema'yla bozusabilir (orn. bir schema degisikligi yapilmis ama bu
        ; DB'de uygulanmamis ise). Bu yuzden yedek alma adimi guvence saglar -
        ; install bozuk gitse bile bu yedekten geri donulebilir.
        ;
        ; Yedek dosya adi: at_install_backup_${BUILD_DATE}.sql
        ; BUILD_DATE bu dosyanin basinda elle tanimlanir; her .exe build edisinde
        ; version.txt yaninda BUILD_DATE de guncellenmelidir.
        DetailPrint "Veritabani zaten mevcut, yedek aliniyor..."
        ExecWait '"$SYSDIR\cmd.exe" /C ""C:\xampp\mysql\bin\mysqldump.exe" -u root anime_tracker > "$DESKTOP\at_install_backup_${BUILD_DATE}.sql""'
        DetailPrint "Yedek alindi: $DESKTOP\at_install_backup_${BUILD_DATE}.sql"
    ${EndIf}
    
    ; config.php olustur
    ; .exe ile kurulan kullanicilar setup.php'yi gormez (silinecek), bu yuzden
    ; config.php'yi installer dogrudan yazmali. XAMPP varsayilanlari kullaniliyor:
    ; localhost host, root kullanici, bos sifre. Bu bilgiler XAMPP'in kendi
    ; kurulum varsayilanlariyla birebir esleshir.
    ;
    ; ANIMESCHEDULE_API_KEY yorum satiri olarak eklenir cunku token kullaniciya
    ; ozel ve installer onu bilemez. Kullanici "Otomatik Doldur" butonunu kullanmak
    ; isterse config.php'yi acip son satirdaki // isaretini silip kendi token'ini
    ; yapistirir. Bu kullanici icin acik bir self-serve yol — example dosyaya
    ; bakmaya gerek kalmaz.
    DetailPrint "config.php olusturuluyor..."
    FileOpen $2 "$OUTDIR\config.php" w
    FileWrite $2 "<?php$\r$\n"
    FileWrite $2 "define('DB_HOST', 'localhost');$\r$\n"
    FileWrite $2 "define('DB_NAME', 'anime_tracker');$\r$\n"
    FileWrite $2 "define('DB_USER', 'root');$\r$\n"
    FileWrite $2 "define('DB_PASS', '');$\r$\n"
    FileWrite $2 "$\r$\n"
    FileWrite $2 "// AnimeSchedule API anahtari (opsiyonel - 'Otomatik Doldur' butonu icin)$\r$\n"
    FileWrite $2 "// Token almak icin:$\r$\n"
    FileWrite $2 "//   1. https://animeschedule.net adresinde hesap acin$\r$\n"
    FileWrite $2 "//   2. https://animeschedule.net/users/<kullaniciadi>/settings/api sayfasini acin$\r$\n"
    FileWrite $2 "//   3. Yeni uygulama olusturun (animelist scope GEREKMEZ)$\r$\n"
    FileWrite $2 "//   4. Token degerini (ID degil) kopyalayin$\r$\n"
    FileWrite $2 "//   5. Asagidaki satirin basindaki // isaretini silin ve token'i yapistirin$\r$\n"
    FileWrite $2 "// define('ANIMESCHEDULE_API_KEY', 'token_buraya_gelecek');$\r$\n"
    FileClose $2
    
    ; Kurulum sonrasi temizlik: setup.php ve install.php sadece manuel
    ; kurulum yolu icin gereklidir. .exe ile kurulan kullanicilarda bu
    ; dosyalarin tarayicidan ulasilabilir kalmasi guvenlik riskidir
    ; (orn. biri DB sifresini sifirlayabilir veya schema'yi yeniden yukleyebilir).
    DetailPrint "Kurulum sonrasi temizlik yapiliyor..."
    Delete "$OUTDIR\setup.php"
    Delete "$OUTDIR\install.php"
SectionEnd

Section "Masaüstü Kısayolu" SecDesktop
    ; CreateShortCut parametre sirasi:
    ;   1. .lnk dosyasinin yolu
    ;   2. Hedef (acilacak URL veya program)
    ;   3. Hedefin parametreleri (URL icin bos)
    ;   4. Ikonun yolu
    ;   5. Ikon index (multi-icon ICO icin, 0 = ilk)
    CreateShortCut "$DESKTOP\Anime Tracker.lnk" "http://localhost/anime_tracker" "" "$INSTDIR\anime-tracker-icon.ico" 0
SectionEnd

Section "Uninstall"
    ; Yedek alma cmd.exe ile wrap edilir cunku ExecWait dogrudan bir shell
    ; baslatmaz ve > redirect operatorunu yorumlayamaz. Bu adim servisler
    ; durdurulmadan ONCE calismalidir, aksi halde mysqldump baglanamaz.
    ExecWait '"$SYSDIR\cmd.exe" /C ""C:\xampp\mysql\bin\mysqldump.exe" -u root anime_tracker > "$DESKTOP\anime_tracker_backup.sql""'
    
    ; Apache'yi gecici olarak durdur ki htdocs/anime_tracker silinebilsin.
    ; Apache calisirken htdocs altindaki PHP dosyalari kilitli olabilir,
    ; bu yuzden RMDir basarisiz olabilir. Servis adi tirnaklar arasinda
    ; cunku icerisinde nokta var.
    DetailPrint "Apache gecici olarak durduruluyor..."
    ExecWait 'net stop "Apache2.4"'
    
    ; Anime Tracker dosyalarini sil
    RMDir /r "C:\xampp\htdocs\anime_tracker"
    
    ; Apache'yi yeniden baslat ki kullanicinin diger XAMPP siteleri etkilenmesin.
    ; MySQL'e dokunulmuyor cunku DB silinmedi (yedek alindi ama drop edilmedi),
    ; veriler ileride yeniden kurulumda kullanilabilir.
    DetailPrint "Apache yeniden baslatiliyor..."
    ExecWait 'net start "Apache2.4"'
    
    Delete "$DESKTOP\Anime Tracker.lnk"
    
    DeleteRegKey HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\AnimeTracker"
    
    Delete "$INSTDIR\anime-tracker-icon.ico"
    Delete "$INSTDIR\uninstall.exe"
    RMDir "$INSTDIR"
    
    MessageBox MB_OK|MB_ICONINFORMATION "Anime Tracker kaldirildi.$\n$\nVeritabani yedegi masaustune kaydedildi: anime_tracker_backup.sql$\n$\nNot: Veritabaninin kendisi silinmedi, sadece yedeklendi. Yeniden kurulumda mevcut veriler kullanilabilir."
SectionEnd

; Bileşen açıklamaları
!insertmacro MUI_FUNCTION_DESCRIPTION_BEGIN
    !insertmacro MUI_DESCRIPTION_TEXT ${SecXAMPP} "Web sunucusu ve veritabanı (Gerekli)"
    !insertmacro MUI_DESCRIPTION_TEXT ${SecMain} "Anime Tracker ana uygulama dosyaları (Gerekli)"
    !insertmacro MUI_DESCRIPTION_TEXT ${SecDesktop} "Masaüstüne kısayol oluşturur"
!insertmacro MUI_FUNCTION_DESCRIPTION_END

Function LaunchApplication
    ExecShell "open" "http://localhost/anime_tracker"
FunctionEnd

Function .onInit
    MessageBox MB_ICONINFORMATION|MB_OK "Kurulum sihirbazına hoş geldiniz!$\n$\nDevam etmeden önce lütfen tüm açık uygulamaları kapatın."
FunctionEnd

Function .onInstSuccess
    MessageBox MB_ICONINFORMATION|MB_OK "Kurulum başarıyla tamamlandı!"
FunctionEnd