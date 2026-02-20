<?php
// languages
$translations = [
    'en' => [
        'settings' => 'Settings',
        'add_account' => 'Add Account',
        'language' => 'Language',
        'time_date' => 'Time And Date',
        'display_accessibility' => 'Display And Accessibility',
        'language_settings' => 'Language Settings',
        'select_language' => 'Select your preferred language:',
        'save' => 'Save',
        'back_to_settings' => 'Back to Settings',
        'language_updated' => 'Language updated successfully!'
    ],
    'es' => [
        'settings' => 'Configuración',
        'add_account' => 'Agregar Cuenta',
        'language' => 'Idioma',
        'time_date' => 'Fecha y Hora',
        'display_accessibility' => 'Pantalla y Accesibilidad',
        'language_settings' => 'Configuración de Idioma',
        'select_language' => 'Selecciona tu idioma preferido:',
        'save' => 'Guardar',
        'back_to_settings' => 'Volver a Configuración',
        'language_updated' => '¡Idioma actualizado exitosamente!'
    ],
    'fr' => [
        'settings' => 'Paramètres',
        'add_account' => 'Ajouter un Compte',
        'language' => 'Langue',
        'time_date' => 'Date et Heure',
        'display_accessibility' => 'Affichage et Accessibilité',
        'language_settings' => 'Paramètres de Langue',
        'select_language' => 'Sélectionnez votre langue préférée:',
        'save' => 'Enregistrer',
        'back_to_settings' => 'Retour aux Paramètres',
        'language_updated' => 'Langue mise à jour avec succès!'
    ],
    'fl' => [
        'settings' => 'Mga Setting',
        'add_account' => 'Magdagdag ng Account',
        'language' => 'Wika',
        'time_date' => 'Oras at Petsa',
        'display_accessibility' => 'Display at Accessibility',
        'language_settings' => 'Mga Setting ng Wika',
        'select_language' => 'Pumili ng iyong gustong wika:',
        'save' => 'I-save',
        'back_to_settings' => 'Bumalik sa Mga Setting',
        'language_updated' => 'Matagumpay na na-update ang wika!'
    ]
];


function translate($key) {
    global $translations;
    $language = $_SESSION['language'] ?? 'en';
    return $translations[$language][$key] ?? $key;
}
?>