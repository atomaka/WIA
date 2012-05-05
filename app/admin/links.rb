ActiveAdmin.register Link do
  index do
    column :id
    column :url
    column :description
    column :release
    default_actions
  end
end
