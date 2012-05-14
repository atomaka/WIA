ActiveAdmin.register Blog do
  index do
    column :id
    column :title
    column :release
    default_actions
  end
end
